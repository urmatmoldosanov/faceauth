#!/usr/bin/env bash
set -euo pipefail

OUTPUT_DIR="${1:-collected_project}"
ROOT_DIR="$(git rev-parse --show-toplevel 2>/dev/null || pwd)"
OUTPUT_PATH="$ROOT_DIR/$OUTPUT_DIR"

if [[ "$OUTPUT_DIR" = /* ]]; then
  echo "Output directory must be relative to the repository root: $OUTPUT_DIR" >&2
  exit 1
fi

rm -rf "$OUTPUT_PATH"
mkdir -p "$OUTPUT_PATH"

# Copy the project into one directory while excluding VCS metadata and generated/heavy folders.
tar \
  --exclude='./.git' \
  --exclude="./$OUTPUT_DIR" \
  --exclude='./node_modules' \
  --exclude='./dist' \
  --exclude='./build' \
  --exclude='./coverage' \
  --exclude='./.pytest_cache' \
  --exclude='./.mypy_cache' \
  --exclude='./__pycache__' \
  -C "$ROOT_DIR" \
  -cf - . | tar -C "$OUTPUT_PATH" -xf -

printf 'Project files collected in %s\n' "$OUTPUT_PATH"
