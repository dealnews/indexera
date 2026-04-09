#!/bin/bash

docker buildx build \
    --platform linux/amd64,linux/arm64 \
    -t dealnews/indexera:latest \
    -t dealnews/indexera:$1 \
    --push \
    .
