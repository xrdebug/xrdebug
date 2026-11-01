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
	"crypto/ed25519"
	"crypto/rand"
	"crypto/x509"
	"encoding/base64"
	"encoding/json"
	"encoding/pem"
	"errors"
	"fmt"
	"os"
	"path/filepath"
	"strconv"
	"strings"
	"sync"
)

const (
	settingsFile      = "settings.json"
	managedSymmetric  = "symmetric.key"
	managedSignKey    = "sign-private.pem"
	defaultPort       = 27420
	defaultEditor     = "vscode"
	defaultSessionKey = "xrDebug"
)

var editors = []string{
	"atom", "bracket", "emacs", "espresso", "fleet", "idea", "macvim",
	"netbeans", "nova", "phpstorm", "sublime", "textmate", "vscode", "zed",
}

// Settings mirrors the xrdebug CLI flags. Empty key paths mean the app
// manages (generates and persists) the key in its support directory.
type Settings struct {
	Address                string `json:"address"`
	Port                   int    `json:"port"`
	SessionName            string `json:"sessionName"`
	Editor                 string `json:"editor"`
	EnableEncryption       bool   `json:"enableEncryption"`
	SymmetricKey           string `json:"symmetricKey"`
	EnableSignVerification bool   `json:"enableSignVerification"`
	SignPrivateKey         string `json:"signPrivateKey"`
	TLSCert                string `json:"tlsCert"`
	TLSPrivateKey          string `json:"tlsPrivateKey"`
}

func DefaultSettings() Settings {
	return Settings{
		Port:        defaultPort,
		SessionName: defaultSessionKey,
		Editor:      defaultEditor,
	}
}

// Validate checks the settings before handing them to the CLI.
func (s Settings) Validate() error {
	var errs []error
	if s.Port < 0 || s.Port > 65535 {
		errs = append(errs, fmt.Errorf("port must be between 0 and 65535"))
	}
	if strings.TrimSpace(s.SessionName) == "" {
		errs = append(errs, fmt.Errorf("session name is required"))
	}
	valid := false
	for _, e := range editors {
		if e == s.Editor {
			valid = true
		}
	}
	if !valid {
		errs = append(errs, fmt.Errorf("editor '%s' not supported", s.Editor))
	}
	if (s.TLSCert == "") != (s.TLSPrivateKey == "") {
		errs = append(errs, fmt.Errorf("TLS needs both a certificate and a private key"))
	}
	for _, path := range []string{s.TLSCert, s.TLSPrivateKey} {
		if path == "" {
			continue
		}
		if _, err := os.Stat(path); err != nil {
			errs = append(errs, fmt.Errorf("file '%s' not found", path))
		}
	}
	if s.EnableEncryption && s.SymmetricKey != "" {
		if _, err := os.Stat(s.SymmetricKey); err != nil {
			errs = append(errs, fmt.Errorf("symmetric key '%s' not found", s.SymmetricKey))
		}
	}
	if s.EnableSignVerification && s.SignPrivateKey != "" {
		if _, err := os.Stat(s.SignPrivateKey); err != nil {
			errs = append(errs, fmt.Errorf("sign private key '%s' not found", s.SignPrivateKey))
		}
	}
	return errors.Join(errs...)
}

// Store persists Settings and the app-managed keys.
type Store struct {
	mu  sync.Mutex
	dir string
}

func NewStore() (*Store, error) {
	base, err := os.UserConfigDir()
	if err != nil {
		return nil, err
	}
	dir := filepath.Join(base, "xrDebug")
	if err := os.MkdirAll(dir, 0o700); err != nil {
		return nil, err
	}
	return &Store{dir: dir}, nil
}

func (st *Store) Dir() string {
	return st.dir
}

func (st *Store) Load() Settings {
	st.mu.Lock()
	defer st.mu.Unlock()
	settings := DefaultSettings()
	data, err := os.ReadFile(filepath.Join(st.dir, settingsFile))
	if err != nil {
		return settings
	}
	if err := json.Unmarshal(data, &settings); err != nil {
		return DefaultSettings()
	}
	return settings
}

func (st *Store) Save(s Settings) error {
	st.mu.Lock()
	defer st.mu.Unlock()
	data, err := json.MarshalIndent(s, "", "  ")
	if err != nil {
		return err
	}
	return os.WriteFile(filepath.Join(st.dir, settingsFile), data, 0o600)
}

func (st *Store) symmetricKeyPath() string {
	return filepath.Join(st.dir, managedSymmetric)
}

func (st *Store) signKeyPath() string {
	return filepath.Join(st.dir, managedSignKey)
}

// ensureManagedKeys creates the app-managed key files when a feature is
// enabled without a user-provided key, so keys survive server restarts.
func (st *Store) ensureManagedKeys(s Settings) error {
	if s.EnableEncryption && s.SymmetricKey == "" {
		if err := writeIfMissing(st.symmetricKeyPath(), func() ([]byte, error) {
			key := make([]byte, 32)
			if _, err := rand.Read(key); err != nil {
				return nil, err
			}
			return []byte(base64.StdEncoding.EncodeToString(key) + "\n"), nil
		}); err != nil {
			return fmt.Errorf("generate symmetric key: %w", err)
		}
	}
	if s.EnableSignVerification && s.SignPrivateKey == "" {
		if err := writeIfMissing(st.signKeyPath(), func() ([]byte, error) {
			_, private, err := ed25519.GenerateKey(nil)
			if err != nil {
				return nil, err
			}
			pkcs8, err := x509.MarshalPKCS8PrivateKey(private)
			if err != nil {
				return nil, err
			}
			return pem.EncodeToMemory(&pem.Block{Type: "PRIVATE KEY", Bytes: pkcs8}), nil
		}); err != nil {
			return fmt.Errorf("generate sign key: %w", err)
		}
	}
	return nil
}

// RegenerateKeys deletes the app-managed keys so new ones get generated.
func (st *Store) RegenerateKeys() error {
	for _, path := range []string{st.symmetricKeyPath(), st.signKeyPath()} {
		if err := os.Remove(path); err != nil && !os.IsNotExist(err) {
			return err
		}
	}
	return nil
}

// Args builds the xrdebug CLI arguments, generating managed keys as needed.
func (st *Store) Args(s Settings) ([]string, error) {
	if err := st.ensureManagedKeys(s); err != nil {
		return nil, err
	}
	args := []string{
		"-p", strconv.Itoa(s.Port),
		"-n", s.SessionName,
		"-i", s.Editor,
	}
	if s.Address != "" {
		args = append(args, "-a", s.Address)
	}
	if s.TLSCert != "" && s.TLSPrivateKey != "" {
		args = append(args, "-c", s.TLSCert, "-z", s.TLSPrivateKey)
	}
	if s.EnableEncryption {
		args = append(args, "-e", "-k", st.keyPath(s.SymmetricKey, st.symmetricKeyPath()))
	}
	if s.EnableSignVerification {
		args = append(args, "-s", "-x", st.keyPath(s.SignPrivateKey, st.signKeyPath()))
	}
	return args, nil
}

func (st *Store) keyPath(custom, managed string) string {
	if custom != "" {
		return custom
	}
	return managed
}

// Keys returns the key material clients need to talk to the server.
type Keys struct {
	SymmetricKey   string `json:"symmetricKey"`
	SignPrivateKey string `json:"signPrivateKey"`
}

func (st *Store) Keys(s Settings) Keys {
	var keys Keys
	if s.EnableEncryption {
		if data, err := os.ReadFile(st.keyPath(s.SymmetricKey, st.symmetricKeyPath())); err == nil {
			keys.SymmetricKey = strings.TrimSpace(string(data))
		}
	}
	if s.EnableSignVerification {
		if data, err := os.ReadFile(st.keyPath(s.SignPrivateKey, st.signKeyPath())); err == nil {
			keys.SignPrivateKey = strings.TrimSpace(string(data))
		}
	}
	return keys
}

func writeIfMissing(path string, generate func() ([]byte, error)) error {
	if _, err := os.Stat(path); err == nil {
		return nil
	}
	data, err := generate()
	if err != nil {
		return err
	}
	return os.WriteFile(path, data, 0o600)
}
