#!/usr/bin/env bash

set -e

docker run --rm --init -i -p 27420:27420 ghcr.io/chevere/xr
