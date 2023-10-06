#!/usr/bin/env bash

set -e

CURRENT_DIRECTORY="$(cd "$(dirname "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
PARENT_DIRECTORY="${CURRENT_DIRECTORY%/*}"

mkdir -p ${PARENT_DIRECTORY}/bin/macos/{arm64,x86_64}
mkdir -p ${PARENT_DIRECTORY}/bin/linux/{aarch64,x86_64}

cat ${PARENT_DIRECTORY}/micro/micro-macos-arm64.sfx ${PARENT_DIRECTORY}/xrdebug.phar >${PARENT_DIRECTORY}/bin/macos/arm64/xrdebug
cat ${PARENT_DIRECTORY}/micro/micro-macos-x86_64.sfx ${PARENT_DIRECTORY}/xrdebug.phar >${PARENT_DIRECTORY}/bin/macos/x86_64/xrdebug
cat ${PARENT_DIRECTORY}/micro/micro-linux-aarch64.sfx ${PARENT_DIRECTORY}/xrdebug.phar >${PARENT_DIRECTORY}/bin/linux/aarch64/xrdebug
cat ${PARENT_DIRECTORY}/micro/micro-linux-x86_64.sfx ${PARENT_DIRECTORY}/xrdebug.phar >${PARENT_DIRECTORY}/bin/linux/x86_64/xrdebug

chmod +x ${PARENT_DIRECTORY}/bin/macos/*/xrdebug
chmod +x ${PARENT_DIRECTORY}/bin/linux/*/xrdebug
