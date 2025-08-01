#!/bin/bash
set -euo pipefail

# Determine repository root (directory containing this script's parent)
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"

TMP_DIR="$(mktemp -d)"
trap 'rm -rf "$TMP_DIR"' EXIT

# Copy plugin PHP file
cp "$ROOT_DIR/cookie-consent-king.php" "$TMP_DIR/"

# Copy built assets
if [[ -d "$ROOT_DIR/dist" ]]; then
  cp -R "$ROOT_DIR/dist" "$TMP_DIR/dist"
else
  echo "dist/ directory not found. Run 'npm run build' first." >&2
  exit 1
fi

# Copy compiled translations (.mo files)
if ls "$ROOT_DIR"/languages/*.mo >/dev/null 2>&1; then
  mkdir -p "$TMP_DIR/languages"
  cp "$ROOT_DIR"/languages/*.mo "$TMP_DIR/languages/"
fi

ZIP_PATH="$ROOT_DIR/cookie-consent-king.zip"

( cd "$TMP_DIR" && zip -r "$ZIP_PATH" . )

echo "Created $ZIP_PATH"
