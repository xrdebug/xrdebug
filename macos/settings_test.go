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
	"encoding/base64"
	"os"
	"path/filepath"
	"slices"
	"strings"
	"testing"
)

func TestValidate(t *testing.T) {
	if err := DefaultSettings().Validate(); err != nil {
		t.Fatalf("defaults should be valid: %v", err)
	}
	s := DefaultSettings()
	s.Port = 70000
	s.Editor = "notepad"
	s.TLSCert = "/nope.pem"
	err := s.Validate()
	if err == nil {
		t.Fatal("expected errors")
	}
	for _, want := range []string{"port", "notepad", "TLS"} {
		if !strings.Contains(err.Error(), want) {
			t.Errorf("error %q should mention %q", err, want)
		}
	}
}

func TestStoreRoundTrip(t *testing.T) {
	st := &Store{dir: t.TempDir()}
	if got := st.Load(); got != DefaultSettings() {
		t.Fatalf("expected defaults, got %+v", got)
	}
	s := DefaultSettings()
	s.Port = 9000
	s.SessionName = "Mine"
	if err := st.Save(s); err != nil {
		t.Fatal(err)
	}
	if got := st.Load(); got != s {
		t.Fatalf("got %+v want %+v", got, s)
	}
}

func TestArgsManagedKeys(t *testing.T) {
	st := &Store{dir: t.TempDir()}
	s := DefaultSettings()
	s.Address = "127.0.0.1"
	s.EnableEncryption = true
	s.EnableSignVerification = true
	args, err := st.Args(s)
	if err != nil {
		t.Fatal(err)
	}
	want := []string{
		"-p", "27420", "-n", "xrDebug", "-i", "vscode", "-a", "127.0.0.1",
		"-e", "-k", filepath.Join(st.dir, managedSymmetric),
		"-s", "-x", filepath.Join(st.dir, managedSignKey),
	}
	if !slices.Equal(args, want) {
		t.Fatalf("got %v\nwant %v", args, want)
	}
	keys := st.Keys(s)
	raw, err := base64.StdEncoding.DecodeString(keys.SymmetricKey)
	if err != nil || len(raw) != 32 {
		t.Fatalf("bad symmetric key %q", keys.SymmetricKey)
	}
	if !strings.HasPrefix(keys.SignPrivateKey, "-----BEGIN PRIVATE KEY-----") {
		t.Fatalf("bad sign key %q", keys.SignPrivateKey)
	}
	// Keys are stable across calls until regenerated.
	if again := st.Keys(s); again != keys {
		t.Fatal("managed keys changed without regeneration")
	}
	if err := st.RegenerateKeys(); err != nil {
		t.Fatal(err)
	}
	if _, err := os.Stat(filepath.Join(st.dir, managedSymmetric)); !os.IsNotExist(err) {
		t.Fatal("expected managed key to be removed")
	}
}
