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
	"encoding/json"
	"fmt"
	"net/url"
	"os"
	"path/filepath"
	"strings"
)

const bridgePrefix = "xrdebug:"

// bridgeJS runs in the main window after every navigation. WKWebView can't
// follow custom URL schemes (editor links) nor save `<a download>` links, so
// those are forwarded to Go through the webview message handler.
const bridgeJS = `(function () {
  if (window.__xrdebugBridge) return;
  window.__xrdebugBridge = true;
  var post = function (msg) {
    try {
      window.webkit.messageHandlers.external.postMessage("` + bridgePrefix + `" + JSON.stringify(msg));
    } catch (e) {}
  };
  var handle = function (a) {
    if (!a || !a.href) return false;
    if (a.hasAttribute("download")) {
      post({ type: "download", url: a.href, name: a.getAttribute("download") || "download" });
      return true;
    }
    var target;
    try { target = new URL(a.href, location.href); } catch (e) { return false; }
    if (target.origin === location.origin && !a.target) return false;
    post({ type: "open", url: target.href });
    return true;
  };
  document.addEventListener("click", function (e) {
    var a = e.target && e.target.closest ? e.target.closest("a[href]") : null;
    if (a && handle(a)) e.preventDefault();
  }, true);
  var click = HTMLAnchorElement.prototype.click;
  HTMLAnchorElement.prototype.click = function () {
    if (!this.isConnected && handle(this)) return;
    return click.apply(this, arguments);
  };
  window.open = function (u) {
    if (u) post({ type: "open", url: new URL(u, location.href).href });
    return null;
  };
})();`

// openSchemes are the URL schemes the main window may hand to macOS.
var openSchemes = map[string]bool{
	"http": true, "https": true, "mailto": true,
	"atom": true, "brackethq": true, "emacs": true, "x-espresso": true,
	"fleet": true, "idea": true, "mvim": true, "netbeans": true, "nova": true,
	"phpstorm": true, "subl": true, "txmt": true, "vscode": true, "zed": true,
}

type bridgeMessage struct {
	Type string `json:"type"`
	URL  string `json:"url"`
	Name string `json:"name"`
}

func parseBridgeMessage(raw string) (bridgeMessage, bool) {
	var msg bridgeMessage
	if !strings.HasPrefix(raw, bridgePrefix) {
		return msg, false
	}
	if err := json.Unmarshal([]byte(strings.TrimPrefix(raw, bridgePrefix)), &msg); err != nil {
		return msg, false
	}
	return msg, true
}

// openableURL reports whether the URL uses a scheme we hand to macOS.
func openableURL(raw string) bool {
	u, err := url.Parse(raw)
	if err != nil {
		return false
	}
	return openSchemes[strings.ToLower(u.Scheme)]
}

// decodeDataURL decodes a base64 `data:` URL.
func decodeDataURL(raw string) ([]byte, error) {
	if !strings.HasPrefix(raw, "data:") {
		return nil, fmt.Errorf("unsupported download URL")
	}
	meta, payload, found := strings.Cut(strings.TrimPrefix(raw, "data:"), ",")
	if !found {
		return nil, fmt.Errorf("malformed data URL")
	}
	if strings.HasSuffix(meta, ";base64") {
		return base64.StdEncoding.DecodeString(payload)
	}
	decoded, err := url.PathUnescape(payload)
	return []byte(decoded), err
}

// safeFilename strips path separators from a suggested download name.
func safeFilename(name string) string {
	name = filepath.Base(strings.ReplaceAll(name, ":", "-"))
	if name == "." || name == string(os.PathSeparator) || name == "" {
		return "download"
	}
	return name
}
