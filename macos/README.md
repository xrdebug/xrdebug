# xrDebug for macOS

A native macOS app for xrDebug, built with [Wails v3](https://v3.wails.io). It bundles the `xrdebug` CLI inside `xrDebug.app`, runs it as a child process with the options saved in the app, and shows the debugger UI in a native window.

## Features

- Native window running the xrDebug web UI, with the server kept alive when the window is closed (click the Dock icon to bring it back).
- Settings window (⌘,) for every CLI option: address, port, session name, editor, end-to-end encryption, sign verification and TLS.
- App-managed encryption and sign keys, generated once and kept across restarts. The settings window shows them so you can copy them into your clients.
- Editor links (`vscode://`, `phpstorm://`, …) open in your editor, and message export saves PNGs through a native save dialog.
- Server menu: restart, copy URL, open in browser and show the server log.
- **xrDebug › Install Command Line Tool…** symlinks the bundled CLI to `/usr/local/bin/xrdebug`.

Settings and keys live in `~/Library/Application Support/xrDebug`.

## Build

Requires macOS, Go 1.26+ and Xcode command line tools. The `wails3` CLI is installed on demand.

```sh
make dmg
```

This builds universal (arm64 + x86_64) binaries for the CLI and the app, assembles `bin/xrDebug.app`, ad-hoc signs it and creates `bin/xrDebug-<version>-macos-universal.dmg`. The version comes from `../meta.go`.

Other targets:

| Target       | Description                                              |
| ------------ | -------------------------------------------------------- |
| `make app`   | Build and sign `bin/xrDebug.app` only                    |
| `make run`   | Build `bin/xrDebug.app` and run it with logs in the terminal |
| `make test`  | Run the unit tests                                       |
| `make clean` | Remove `bin/`                                            |

### Signing and notarization

By default the app is ad-hoc signed, so Gatekeeper will ask users to confirm the first launch (right click › Open). To sign with a Developer ID identity, which also enables the hardened runtime:

```sh
make dmg SIGN_IDENTITY="Developer ID Application: Name (TEAMID)"
```

Then notarize the `.dmg` with `xcrun notarytool submit … --wait` and `xcrun stapler staple`.

## How it works

- `server.go` starts the CLI, parses `Running at <url>` from its output, and stops it on quit. A PID file lets the next launch stop a server orphaned by a crash.
- `settings.go` stores the settings and turns them into CLI flags.
- `bridge.go` injects a small script into the UI page that forwards custom-scheme links and `<a download>` exports to Go, since WKWebView can't handle either on its own.
- `frontend/` holds the loading page and the settings window, which talks to `SettingsService` (`service.go`) through the Wails runtime.

TLS with a self-signed certificate will not load in the app window, because WKWebView rejects untrusted certificates. Use a trusted certificate (for example one made with `mkcert`) or open the server in a browser.
