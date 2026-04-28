#!/usr/bin/env bash
set -euo pipefail

BASE_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHOTOS_DIR="${FACEAUTH_STORAGE_PHOTOS:-${BASE_DIR}/storage/photos}"
LOGS_DIR="${FACEAUTH_STORAGE_LOGS:-${BASE_DIR}/storage/logs}"

mkdir -p "$PHOTOS_DIR" "$LOGS_DIR"
chmod 770 "$PHOTOS_DIR" "$LOGS_DIR"

echo "Storage prepared:"
echo "  photos: $PHOTOS_DIR"
echo "  logs:   $LOGS_DIR"
