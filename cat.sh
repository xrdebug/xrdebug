#!/usr/bin/env bash

set -e

mkdir -p bin/macos/{arm64,x86_64}
mkdir -p bin/linux/{aarch64,x86_64}

cat micro/micro-macos-arm64.sfx xrdebug.phar >bin/macos/arm64/xrdebug
cat micro/micro-macos-x86_64.sfx xrdebug.phar >bin/macos/x86_64/xrdebug
cat micro/micro-linux-aarch64.sfx xrdebug.phar >bin/linux/aarch64/xrdebug
cat micro/micro-linux-x86_64.sfx xrdebug.phar >bin/linux/x86_64/xrdebug

chmod +x bin/macos/*/xrdebug
chmod +x bin/linux/*/xrdebug
