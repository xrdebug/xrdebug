#!/usr/bin/env bash

set -e

docker run --pull=always --rm --init -i -p 27420:27420 ghcr.io/chevere/xrdebug:latest
