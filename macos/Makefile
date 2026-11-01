APP_NAME := xrDebug
APP_EXEC := xrdebug-app
VERSION ?= $(shell sed -n 's/.*version *= *"\(.*\)"/\1/p' ../meta.go)
BIN_DIR := bin
APP := $(BIN_DIR)/$(APP_NAME).app
DMG := $(BIN_DIR)/$(APP_NAME)-$(VERSION)-macos-universal.dmg
SIGN_IDENTITY ?= -
# Hardened runtime + timestamp only for real (Developer ID) identities.
SIGN_FLAGS := $(if $(filter -,$(SIGN_IDENTITY)),,--options runtime --timestamp)

export CGO_ENABLED := 1
export MACOSX_DEPLOYMENT_TARGET := 12.0
export CGO_CFLAGS := -mmacosx-version-min=12.0
export CGO_LDFLAGS := -mmacosx-version-min=12.0

WAILS3 ?= $(shell command -v wails3 || echo $(shell go env GOPATH)/bin/wails3)
GO_FLAGS := -tags production -trimpath -buildvcs=false -ldflags="-s -w"

.PHONY: all run dev app dmg cli clean vet test wails3 icons

all: dmg

# Builds the xrdebug CLI (universal) from the repository root.
cli:
	@mkdir -p $(BIN_DIR)
	cd .. && GOOS=darwin GOARCH=arm64 CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o macos/$(BIN_DIR)/xrdebug-arm64 .
	cd .. && GOOS=darwin GOARCH=amd64 CGO_ENABLED=0 go build -trimpath -ldflags="-s -w" -o macos/$(BIN_DIR)/xrdebug-amd64 .
	lipo -create -output $(BIN_DIR)/xrdebug $(BIN_DIR)/xrdebug-arm64 $(BIN_DIR)/xrdebug-amd64
	rm $(BIN_DIR)/xrdebug-arm64 $(BIN_DIR)/xrdebug-amd64

# Builds the Wails app executable (universal).
$(BIN_DIR)/$(APP_EXEC): $(wildcard *.go) $(wildcard frontend/*) go.mod
	@mkdir -p $(BIN_DIR)
	GOOS=darwin GOARCH=arm64 go build $(GO_FLAGS) -o $(BIN_DIR)/$(APP_EXEC)-arm64 .
	GOOS=darwin GOARCH=amd64 go build $(GO_FLAGS) -o $(BIN_DIR)/$(APP_EXEC)-amd64 .
	lipo -create -output $@ $(BIN_DIR)/$(APP_EXEC)-arm64 $(BIN_DIR)/$(APP_EXEC)-amd64
	rm $(BIN_DIR)/$(APP_EXEC)-arm64 $(BIN_DIR)/$(APP_EXEC)-amd64

# Assembles and signs xrDebug.app with the CLI next to the app executable.
app: cli $(BIN_DIR)/$(APP_EXEC)
	rm -rf "$(APP)"
	mkdir -p "$(APP)/Contents/MacOS" "$(APP)/Contents/Resources"
	cp $(BIN_DIR)/$(APP_EXEC) $(BIN_DIR)/xrdebug "$(APP)/Contents/MacOS/"
	cp build/icons.icns build/Assets.car "$(APP)/Contents/Resources/"
	sed 's/@VERSION@/$(VERSION)/g' build/Info.plist > "$(APP)/Contents/Info.plist"
	xattr -cr "$(APP)"
	codesign --force $(SIGN_FLAGS) --sign "$(SIGN_IDENTITY)" "$(APP)/Contents/MacOS/xrdebug"
	codesign --force $(SIGN_FLAGS) --sign "$(SIGN_IDENTITY)" "$(APP)"
	codesign --verify --deep --strict "$(APP)"

# Packages xrDebug.app into a drag-to-Applications .dmg.
dmg: app wails3
	rm -f "$(BIN_DIR)/$(APP_NAME).dmg" "$(DMG)"
	"$(WAILS3)" tool package --format dmg --name "$(APP_NAME)" --out "$(BIN_DIR)" \
		--volume-icon build/icons.icns
	mv "$(BIN_DIR)/$(APP_NAME).dmg" "$(DMG)"
	@echo "Created $(DMG)"

# Regenerates build/Assets.car and build/icons.icns from the Icon Composer
# icon (needs Xcode 26+ actool). The bundle uses Assets.car on macOS 26+,
# which keeps the icon from being shrunk onto a grey tile.
icons: wails3
	"$(WAILS3)" generate icons -iconcomposerinput build/appicon.icon -macassetdir build

wails3:
	@test -x "$(WAILS3)" || go install github.com/wailsapp/wails/v3/cmd/wails3@v3.1.0

# Builds the .app and runs it in the foreground (logs in the terminal).
# Running from the bundle gives the menu bar its xrDebug name and icon.
run: app
	"$(APP)/Contents/MacOS/$(APP_EXEC)"

vet:
	go vet ./...

test:
	go test ./...

clean:
	rm -rf $(BIN_DIR)
