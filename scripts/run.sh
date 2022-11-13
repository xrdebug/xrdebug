#!/usr/bin/env bash

set -e

CURRENT_DIRECTORY="$(cd "$(dirname "${BASH_SOURCE[0]}")" &>/dev/null && pwd)"
PARENT_DIRECTORY="${CURRENT_DIRECTORY%/*}"
${PARENT_DIRECTORY}/xr -p 27420
