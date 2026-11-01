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
	"os/exec"
)

// SettingsService is bound to the settings window.
type SettingsService struct {
	c *Controller
}

func (s *SettingsService) Get() Settings {
	return s.c.store.Load()
}

func (s *SettingsService) Defaults() Settings {
	return DefaultSettings()
}

func (s *SettingsService) Editors() []string {
	return editors
}

// Save validates and stores the settings, then restarts the server.
func (s *SettingsService) Save(settings Settings) error {
	if err := settings.Validate(); err != nil {
		return err
	}
	if err := s.c.store.Save(settings); err != nil {
		return err
	}
	return s.c.StartServer()
}

func (s *SettingsService) Restart() error {
	return s.c.StartServer()
}

func (s *SettingsService) Keys() Keys {
	return s.c.store.Keys(s.c.store.Load())
}

// RegenerateKeys replaces the app-managed keys and restarts the server.
func (s *SettingsService) RegenerateKeys() error {
	if err := s.c.store.RegenerateKeys(); err != nil {
		return err
	}
	return s.c.StartServer()
}

func (s *SettingsService) Status() Status {
	status := s.c.status()
	status.Version = s.c.server.Version()
	return status
}

func (s *SettingsService) Logs() string {
	return s.c.server.Logs()
}

func (s *SettingsService) ClearLogs() {
	s.c.server.ClearLogs()
}

func (s *SettingsService) ShowLog() {
	s.c.ShowLog()
}

func (s *SettingsService) PickFile(title string) (string, error) {
	dialog := s.c.app.Dialog.OpenFile().
		SetTitle(title).
		CanChooseFiles(true).
		ShowHiddenFiles(true)
	if window, ok := s.c.app.Window.GetByName(settingsWindowName); ok {
		dialog.AttachToWindow(window)
	}
	return dialog.PromptForSingleSelection()
}

func (s *SettingsService) Copy(text string) bool {
	return s.c.app.Clipboard.SetText(text)
}

func (s *SettingsService) RevealConfig() error {
	return exec.Command("open", s.c.store.Dir()).Run()
}

func (s *SettingsService) OpenInBrowser() {
	s.c.OpenInBrowser()
}

func (s *SettingsService) ShowMain() {
	s.c.ShowMain()
}
