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
	"fmt"
	"os"
	"os/exec"
	"strings"
	"sync"

	"github.com/wailsapp/wails/v3/pkg/application"
	"github.com/wailsapp/wails/v3/pkg/events"
)

const (
	mainWindowName     = "main"
	settingsWindowName = "settings"
	logWindowName      = "log"
	cliLinkPath        = "/usr/local/bin/xrdebug"
)

// Controller wires the Wails app, the settings store and the CLI process.
type Controller struct {
	app    *application.App
	store  *Store
	server *Server
	main   *application.WebviewWindow

	mu       sync.Mutex
	quitting bool
}

func NewController(store *Store, server *Server) *Controller {
	c := &Controller{store: store, server: server}
	server.OnReady = c.serverReady
	server.OnExit = c.serverExited
	server.OnLog = func(line string) {
		if c.app != nil {
			c.app.Event.Emit("server:log", line)
		}
	}
	return c
}

func (c *Controller) createMainWindow() {
	c.main = c.app.Window.NewWithOptions(application.WebviewWindowOptions{
		Name:      mainWindowName,
		Title:     "xrDebug",
		Width:     1180,
		Height:    820,
		MinWidth:  420,
		MinHeight: 320,
		URL:       "/",
		JS:        bridgeJS,
		Mac: application.MacWindow{
			TitleBar: application.MacTitleBarDefault,
		},
	})
	// Closing the window keeps the server running; the Dock icon brings it back.
	c.main.RegisterHook(events.Common.WindowClosing, func(e *application.WindowEvent) {
		c.mu.Lock()
		quitting := c.quitting
		c.mu.Unlock()
		if quitting {
			return
		}
		e.Cancel()
		c.main.Hide()
	})
	c.app.Event.OnApplicationEvent(events.Mac.ApplicationShouldHandleReopen, func(*application.ApplicationEvent) {
		c.ShowMain()
	})
}

func (c *Controller) ShowMain() {
	c.main.Show()
	c.main.Focus()
}

// StartServer (re)starts the CLI with the stored settings.
func (c *Controller) StartServer() error {
	settings := c.store.Load()
	c.main.SetTitle("xrDebug — " + settings.SessionName)
	c.main.SetURL("/")
	if err := settings.Validate(); err != nil {
		c.showError("Invalid settings", err.Error())
		return err
	}
	args, err := c.store.Args(settings)
	if err != nil {
		c.showError("Unable to prepare keys", err.Error())
		return err
	}
	if err := c.server.Start(args); err != nil {
		c.showError("Unable to start xrDebug", err.Error())
		return err
	}
	return nil
}

func (c *Controller) serverReady(url string) {
	application.InvokeAsync(func() {
		c.main.SetURL(url)
		c.app.Event.Emit("server:status", c.status())
	})
}

func (c *Controller) serverExited(err error, output string) {
	c.mu.Lock()
	quitting := c.quitting
	c.mu.Unlock()
	if quitting {
		return
	}
	application.InvokeAsync(func() {
		c.app.Event.Emit("server:status", c.status())
		dialog := c.app.Dialog.Warning().
			SetTitle("xrDebug server stopped").
			SetMessage(ExitMessage(err, output))
		restart := dialog.AddButton("Restart")
		restart.OnClick(func() { _ = c.StartServer() })
		settings := dialog.AddButton("Settings…")
		settings.OnClick(c.ShowSettings)
		dialog.SetDefaultButton(restart)
		dialog.Show()
	})
}

func (c *Controller) Shutdown() {
	c.mu.Lock()
	c.quitting = true
	c.mu.Unlock()
	c.server.Stop()
}

func (c *Controller) ShowSettings() {
	c.openWindow(application.WebviewWindowOptions{
		Name:      settingsWindowName,
		Title:     "xrDebug Settings",
		Width:     620,
		Height:    640,
		MinWidth:  520,
		MinHeight: 480,
		URL:       "/settings.html",
	})
}

func (c *Controller) ShowLog() {
	c.openWindow(application.WebviewWindowOptions{
		Name:      logWindowName,
		Title:     "xrDebug Server Log",
		Width:     760,
		Height:    480,
		MinWidth:  420,
		MinHeight: 240,
		URL:       "/log.html",
	})
}

// openWindow focuses the named window, creating it on first use.
func (c *Controller) openWindow(options application.WebviewWindowOptions) {
	if window, ok := c.app.Window.GetByName(options.Name); ok {
		window.Show()
		window.Focus()
		return
	}
	c.app.Window.NewWithOptions(options).Focus()
}

func (c *Controller) CopyURL() {
	if url := c.server.URL(); url != "" {
		c.app.Clipboard.SetText(url)
	}
}

func (c *Controller) OpenInBrowser() {
	if url := c.server.URL(); url != "" {
		_ = c.app.Browser.OpenURL(url)
	}
}

// InstallCLI symlinks the bundled CLI into /usr/local/bin (asks for admin).
func (c *Controller) InstallCLI() {
	binary := c.server.Binary()
	script := fmt.Sprintf(
		`do shell script "mkdir -p /usr/local/bin && ln -sf %s %s" with administrator privileges`,
		shellQuote(binary), shellQuote(cliLinkPath),
	)
	out, err := exec.Command("osascript", "-e", script).CombinedOutput()
	if err != nil {
		if strings.Contains(string(out), "-128") {
			return // user cancelled
		}
		c.showError("Unable to install command line tool", strings.TrimSpace(string(out)))
		return
	}
	c.app.Dialog.Info().
		SetTitle("Command line tool installed").
		SetMessage(fmt.Sprintf("`xrdebug` is now available at %s.", cliLinkPath)).
		Show()
}

// handleMessage receives the bridge messages posted by bridgeJS.
func (c *Controller) handleMessage(window application.Window, raw string, _ *application.OriginInfo) {
	if window.Name() != mainWindowName {
		return
	}
	msg, ok := parseBridgeMessage(raw)
	if !ok {
		return
	}
	switch msg.Type {
	case "open":
		if openableURL(msg.URL) {
			_ = c.app.Browser.OpenURL(msg.URL)
		}
	case "download":
		go c.saveDownload(msg)
	}
}

func (c *Controller) saveDownload(msg bridgeMessage) {
	data, err := decodeDataURL(msg.URL)
	if err != nil {
		c.showError("Unable to save file", err.Error())
		return
	}
	home, _ := os.UserHomeDir()
	path, err := c.app.Dialog.SaveFile().
		SetFilename(safeFilename(msg.Name)).
		SetDirectory(home + "/Downloads").
		CanCreateDirectories(true).
		AttachToWindow(c.main).
		PromptForSingleSelection()
	if err != nil || path == "" {
		return
	}
	if err := os.WriteFile(path, data, 0o644); err != nil {
		c.showError("Unable to save file", err.Error())
	}
}

func (c *Controller) showError(title, message string) {
	application.InvokeAsync(func() {
		c.app.Dialog.Error().SetTitle(title).SetMessage(message).Show()
	})
}

// Status is the server state exposed to the settings window.
type Status struct {
	Running   bool   `json:"running"`
	URL       string `json:"url"`
	Version   string `json:"version"`
	Binary    string `json:"binary"`
	ConfigDir string `json:"configDir"`
}

func (c *Controller) status() Status {
	return Status{
		Running:   c.server.Running(),
		URL:       c.server.URL(),
		Binary:    c.server.Binary(),
		ConfigDir: c.store.Dir(),
	}
}

func shellQuote(s string) string {
	// Quoted for the shell, then escaped for the AppleScript string literal.
	quoted := "'" + strings.ReplaceAll(s, "'", `'\''`) + "'"
	return strings.ReplaceAll(strings.ReplaceAll(quoted, `\`, `\\`), `"`, `\"`)
}
