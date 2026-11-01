/*
 * This file is part of xrDebug.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

package main

import (
	"bufio"
	"errors"
	"fmt"
	"io"
	"os"
	"os/exec"
	"path/filepath"
	"strconv"
	"strings"
	"sync"
	"syscall"
	"time"
)

const (
	runningPrefix = "Running at "
	maxLogLines   = 500
)

// Server runs the bundled xrdebug CLI as a child process.
type Server struct {
	binary  string
	pidFile string

	mu         sync.Mutex
	cmd        *exec.Cmd
	done       chan struct{}
	generation int
	url        string
	logs       []string

	// OnReady is called with the server URL once the CLI is listening.
	OnReady func(url string)
	// OnExit is called when the CLI exits on its own (not via Stop).
	OnExit func(err error, output string)
	// OnLog is called for every line the CLI prints.
	OnLog func(line string)
}

// FindBinary locates the xrdebug CLI: next to the app executable (inside the
// .app bundle), then XRDEBUG_BIN, then PATH.
func FindBinary() (string, error) {
	if exe, err := os.Executable(); err == nil {
		candidate := filepath.Join(filepath.Dir(exe), "xrdebug")
		if info, err := os.Stat(candidate); err == nil && !info.IsDir() {
			return candidate, nil
		}
	}
	if env := os.Getenv("XRDEBUG_BIN"); env != "" {
		return env, nil
	}
	return exec.LookPath("xrdebug")
}

// NewServer returns a Server for the CLI binary. pidFile records the child
// PID so a server orphaned by a crashed app can be stopped on next launch.
func NewServer(binary, pidFile string) *Server {
	return &Server{binary: binary, pidFile: pidFile}
}

// killOrphan stops a CLI left running by a previous app process.
func (s *Server) killOrphan() {
	data, err := os.ReadFile(s.pidFile)
	if err != nil {
		return
	}
	defer os.Remove(s.pidFile)
	pid, err := strconv.Atoi(strings.TrimSpace(string(data)))
	if err != nil || pid <= 0 {
		return
	}
	out, err := exec.Command("ps", "-p", strconv.Itoa(pid), "-o", "comm=").Output()
	if err != nil || strings.TrimSpace(string(out)) != s.binary {
		return
	}
	_ = syscall.Kill(-pid, syscall.SIGTERM)
	for range 30 {
		if syscall.Kill(pid, 0) != nil {
			return
		}
		time.Sleep(100 * time.Millisecond)
	}
	_ = syscall.Kill(-pid, syscall.SIGKILL)
}

func (s *Server) Binary() string {
	return s.binary
}

// Version returns the output of `xrdebug -version`.
func (s *Server) Version() string {
	out, err := exec.Command(s.binary, "-version").Output()
	if err != nil {
		return ""
	}
	return strings.TrimSpace(string(out))
}

func (s *Server) URL() string {
	s.mu.Lock()
	defer s.mu.Unlock()
	return s.url
}

func (s *Server) Running() bool {
	s.mu.Lock()
	defer s.mu.Unlock()
	return s.cmd != nil
}

func (s *Server) Logs() string {
	s.mu.Lock()
	defer s.mu.Unlock()
	return strings.Join(s.logs, "\n")
}

// Start launches the CLI with the given arguments, stopping any running one.
func (s *Server) Start(args []string) error {
	s.Stop()
	s.killOrphan()
	cmd := exec.Command(s.binary, args...)
	cmd.SysProcAttr = &syscall.SysProcAttr{Setpgid: true}
	reader, writer := io.Pipe()
	cmd.Stdout = writer
	cmd.Stderr = writer
	if err := cmd.Start(); err != nil {
		writer.Close()
		return fmt.Errorf("start %s: %w", s.binary, err)
	}
	_ = os.WriteFile(s.pidFile, []byte(strconv.Itoa(cmd.Process.Pid)), 0o600)
	s.mu.Lock()
	s.generation++
	generation := s.generation
	s.cmd = cmd
	s.done = make(chan struct{})
	done := s.done
	s.url = ""
	s.logs = nil
	s.mu.Unlock()

	scanned := make(chan struct{})
	go func() {
		s.scan(reader, generation)
		close(scanned)
	}()
	go func() {
		err := cmd.Wait()
		writer.Close()
		<-scanned
		s.mu.Lock()
		current := s.generation == generation
		if current {
			s.cmd = nil
			s.url = ""
			os.Remove(s.pidFile)
		}
		output := strings.Join(s.logs, "\n")
		s.mu.Unlock()
		close(done)
		if current && s.OnExit != nil {
			s.OnExit(err, output)
		}
	}()
	return nil
}

func (s *Server) scan(reader io.Reader, generation int) {
	scanner := bufio.NewScanner(reader)
	scanner.Buffer(make([]byte, 64*1024), 1024*1024)
	for scanner.Scan() {
		line := scanner.Text()
		s.mu.Lock()
		if s.generation != generation {
			s.mu.Unlock()
			continue
		}
		s.logs = append(s.logs, line)
		if len(s.logs) > maxLogLines {
			s.logs = s.logs[len(s.logs)-maxLogLines:]
		}
		var ready string
		if s.url == "" && strings.HasPrefix(line, runningPrefix) {
			s.url = strings.TrimSpace(strings.TrimPrefix(line, runningPrefix))
			ready = s.url
		}
		s.mu.Unlock()
		if s.OnLog != nil {
			s.OnLog(line)
		}
		if ready != "" && s.OnReady != nil {
			s.OnReady(ready)
		}
	}
	// Drain so the child never blocks on a full pipe.
	_, _ = io.Copy(io.Discard, reader)
}

// Stop terminates the running CLI, if any, and waits for it to exit.
func (s *Server) Stop() {
	s.mu.Lock()
	cmd := s.cmd
	done := s.done
	s.generation++
	s.cmd = nil
	s.url = ""
	s.mu.Unlock()
	if cmd == nil || cmd.Process == nil {
		return
	}
	defer os.Remove(s.pidFile)
	pgid := -cmd.Process.Pid
	_ = syscall.Kill(pgid, syscall.SIGTERM)
	select {
	case <-done:
	case <-time.After(3 * time.Second):
		_ = syscall.Kill(pgid, syscall.SIGKILL)
		<-done
	}
}

// ExitMessage describes why the CLI exited.
func ExitMessage(err error, output string) string {
	msg := "The xrDebug server stopped."
	var exitErr *exec.ExitError
	if errors.As(err, &exitErr) {
		msg = fmt.Sprintf("The xrDebug server exited with code %d.", exitErr.ExitCode())
	}
	output = strings.TrimSpace(output)
	if output == "" {
		return msg
	}
	lines := strings.Split(output, "\n")
	if len(lines) > 12 {
		lines = lines[len(lines)-12:]
	}
	return msg + "\n\n" + strings.Join(lines, "\n")
}
