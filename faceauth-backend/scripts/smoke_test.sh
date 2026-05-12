#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${1:-http://faceauth-backend.local}"

echo "[1/3] health"
curl -fsS "${BASE_URL}/health" | sed 's/.*/OK: &/'

echo "[2/3] license status"
curl -fsS "${BASE_URL}/license/status" | sed 's/.*/OK: &/'

echo "[3/3] admin auth check (expected 401 without creds)"
STATUS_CODE=$(curl -s -o /dev/null -w "%{http_code}" "${BASE_URL}/admin/index.php")
if [[ "$STATUS_CODE" != "401" ]]; then
  echo "FAIL: expected 401 for admin without credentials, got ${STATUS_CODE}" >&2
  exit 1
fi
echo "OK: admin is protected (401)"
