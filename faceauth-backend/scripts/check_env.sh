#!/usr/bin/env bash
set -euo pipefail

REQUIRED=(
  FACEAUTH_CORE_URL
  FACEAUTH_TENANT_ID
  FACEAUTH_TENANT_SECRET
  FACEAUTH_MOODLE_SHARED_SECRET
  FACEAUTH_ADMIN_USER
  FACEAUTH_ADMIN_PASS_HASH
)

MISSING=0
for key in "${REQUIRED[@]}"; do
  if [[ -z "${!key:-}" ]]; then
    echo "MISSING: ${key}"
    MISSING=1
  else
    echo "OK: ${key}"
  fi
done

if [[ "$MISSING" -ne 0 ]]; then
  echo "Environment check failed"
  exit 1
fi

echo "Environment check passed"
