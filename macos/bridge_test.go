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
	"testing"
)

func TestParseBridgeMessage(t *testing.T) {
	msg, ok := parseBridgeMessage(`xrdebug:{"type":"open","url":"vscode://file/a.go:1"}`)
	if !ok || msg.Type != "open" || msg.URL != "vscode://file/a.go:1" {
		t.Fatalf("unexpected %+v %v", msg, ok)
	}
	for _, raw := range []string{"wails:foo", `{"type":"open"}`, "xrdebug:not json"} {
		if _, ok := parseBridgeMessage(raw); ok {
			t.Errorf("expected %q to be rejected", raw)
		}
	}
}

func TestOpenableURL(t *testing.T) {
	cases := map[string]bool{
		"vscode://file/a.go:1":          true,
		"phpstorm://open?file=a&line=1": true,
		"https://xrdebug.com":           true,
		"file:///etc/passwd":            false,
		"javascript:alert(1)":           false,
		"x-unknown://foo":               false,
	}
	for raw, want := range cases {
		if got := openableURL(raw); got != want {
			t.Errorf("openableURL(%q) = %v, want %v", raw, got, want)
		}
	}
}

func TestDecodeDataURL(t *testing.T) {
	data, err := decodeDataURL("data:image/png;base64,aGVsbG8=")
	if err != nil || string(data) != "hello" {
		t.Fatalf("got %q %v", data, err)
	}
	data, err = decodeDataURL("data:text/plain,hello%20world")
	if err != nil || string(data) != "hello world" {
		t.Fatalf("got %q %v", data, err)
	}
	if _, err := decodeDataURL("https://example.com/a.png"); err == nil {
		t.Fatal("expected error for non data URL")
	}
}

func TestSafeFilename(t *testing.T) {
	cases := map[string]string{
		"xrDebug-12:00:01.png": "xrDebug-12-00-01.png",
		"../../etc/passwd":     "passwd",
		"":                     "download",
		"/":                    "download",
	}
	for in, want := range cases {
		if got := safeFilename(in); got != want {
			t.Errorf("safeFilename(%q) = %q, want %q", in, got, want)
		}
	}
}
