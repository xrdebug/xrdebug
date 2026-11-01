/*
 * This file is part of xrDebug.
 *
 * (c) Rodolfo Berrios <rodolfo@chevere.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Package main implements the xrDebug macOS application: a Wails v3 shell
// that runs the bundled xrdebug CLI and shows its web UI in a native window.
package main

import (
	"embed"
	"io/fs"
	"log"
	"path/filepath"

	"github.com/wailsapp/wails/v3/pkg/application"
)

//go:embed frontend
var assets embed.FS

//go:embed build/appicon.png
var icon []byte

const (
	name        = "xrDebug"
	description = "Lightweight web-based debug software"
	helpURL     = "https://xrdebug.com"
)

func main() {
	store, err := NewStore()
	if err != nil {
		log.Fatal(err)
	}
	binary, err := FindBinary()
	if err != nil {
		log.Fatalf("xrdebug binary not found: %v", err)
	}
	frontend, err := fs.Sub(assets, "frontend")
	if err != nil {
		log.Fatal(err)
	}
	controller := NewController(store, NewServer(binary, filepath.Join(store.Dir(), "server.pid")))
	app := application.New(application.Options{
		Name:        name,
		Description: description,
		Icon:        icon,
		Services: []application.Service{
			application.NewService(&SettingsService{c: controller}),
		},
		Assets: application.AssetOptions{
			Handler: application.BundledAssetFileServer(frontend),
		},
		Mac: application.MacOptions{
			ApplicationShouldTerminateAfterLastWindowClosed: false,
		},
		SingleInstance: &application.SingleInstanceOptions{
			UniqueID: "com.xrdebug.app",
			OnSecondInstanceLaunch: func(application.SecondInstanceData) {
				controller.ShowMain()
			},
		},
		RawMessageHandler: controller.handleMessage,
		OnShutdown:        controller.Shutdown,
	})
	controller.app = app
	app.Menu.Set(buildMenu(app, controller))
	controller.createMainWindow()
	if err := controller.StartServer(); err != nil {
		controller.ShowSettings()
	}
	if err := app.Run(); err != nil {
		log.Fatal(err)
	}
}

func buildMenu(app *application.App, c *Controller) *application.Menu {
	menu := app.Menu.New()

	appMenu := menu.AddSubmenu(name)
	appMenu.AddRole(application.About)
	appMenu.AddSeparator()
	appMenu.Add("Settings…").SetAccelerator("CmdOrCtrl+,").OnClick(func(*application.Context) {
		c.ShowSettings()
	})
	appMenu.Add("Install Command Line Tool…").OnClick(func(*application.Context) {
		go c.InstallCLI()
	})
	appMenu.AddSeparator()
	appMenu.AddRole(application.ServicesMenu)
	appMenu.AddSeparator()
	appMenu.AddRole(application.Hide)
	appMenu.AddRole(application.HideOthers)
	appMenu.AddRole(application.UnHide)
	appMenu.AddSeparator()
	appMenu.AddRole(application.Quit)

	menu.AddRole(application.EditMenu)

	server := menu.AddSubmenu("Server")
	server.Add("Restart Server").SetAccelerator("CmdOrCtrl+Shift+R").OnClick(func(*application.Context) {
		_ = c.StartServer()
	})
	server.AddSeparator()
	server.Add("Copy Server URL").SetAccelerator("CmdOrCtrl+Shift+C").OnClick(func(*application.Context) {
		c.CopyURL()
	})
	server.Add("Open in Browser").SetAccelerator("CmdOrCtrl+Shift+O").OnClick(func(*application.Context) {
		c.OpenInBrowser()
	})
	server.AddSeparator()
	server.Add("Show Server Log").SetAccelerator("CmdOrCtrl+Shift+L").OnClick(func(*application.Context) {
		c.ShowLog()
	})

	view := menu.AddSubmenu("View")
	view.Add("Reload").SetAccelerator("CmdOrCtrl+R").OnClick(func(*application.Context) {
		c.main.Reload()
	})
	view.AddSeparator()
	view.AddRole(application.ResetZoom)
	view.AddRole(application.ZoomIn)
	view.AddRole(application.ZoomOut)
	view.AddSeparator()
	view.AddRole(application.ToggleFullscreen)

	window := menu.AddSubmenu("Window")
	window.Add("xrDebug").SetAccelerator("CmdOrCtrl+0").OnClick(func(*application.Context) {
		c.ShowMain()
	})
	window.AddSeparator()
	window.AddRole(application.Minimise)
	window.AddRole(application.Zoom)
	window.AddRole(application.CloseWindow)
	window.AddSeparator()
	window.AddRole(application.BringAllToFront)

	help := menu.AddSubmenu("Help")
	help.Add("xrDebug Documentation").OnClick(func(*application.Context) {
		_ = app.Browser.OpenURL(helpURL)
	})
	help.Add("xrDebug on GitHub").OnClick(func(*application.Context) {
		_ = app.Browser.OpenURL("https://github.com/xrdebug/xrdebug")
	})
	return menu
}
