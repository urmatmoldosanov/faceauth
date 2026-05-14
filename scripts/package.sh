#!/usr/bin/env sh
set -eu

ROOT_DIR=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
PACKAGE_DIR="$ROOT_DIR/dist"
PACKAGE_NAME="faceauth-premium-project"
FILE_LIST="$PACKAGE_DIR/$PACKAGE_NAME.files"

mkdir -p "$PACKAGE_DIR"
rm -f "$PACKAGE_DIR/$PACKAGE_NAME.zip" "$PACKAGE_DIR/$PACKAGE_NAME.tar.gz" "$FILE_LIST"

cd "$ROOT_DIR"

find . -type f \
    ! -path './.git/*' \
    ! -path './dist/*' \
    ! -path './config/config.php' \
    ! -path './storage/*.sqlite' \
    ! -path './storage/uploads/*' \
    -print | sort > "$FILE_LIST"

if [ -f './storage/uploads/.gitkeep' ]; then
    printf '%s\n' './storage/uploads/.gitkeep' >> "$FILE_LIST"
fi

if command -v zip >/dev/null 2>&1; then
    zip -q "$PACKAGE_DIR/$PACKAGE_NAME.zip" -@ < "$FILE_LIST"
    rm -f "$FILE_LIST"
    echo "$PACKAGE_DIR/$PACKAGE_NAME.zip"
else
    tar -czf "$PACKAGE_DIR/$PACKAGE_NAME.tar.gz" -T "$FILE_LIST"
    rm -f "$FILE_LIST"
    echo "$PACKAGE_DIR/$PACKAGE_NAME.tar.gz"
fi
