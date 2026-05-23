#!/usr/bin/env bash
# Roll back the webroot to the most recent backup snapshot.
# Run this on the Zone server from the directory ABOVE the webroot.
#
# Usage: ./rollback.sh [target_webroot_name]
#   target_webroot_name defaults to "htdocs"

set -euo pipefail

WEBROOT="${1:-htdocs}"
TIMESTAMP="$(date +%Y%m%d-%H%M%S)"

if [ ! -d "$WEBROOT" ]; then
  echo "Error: no directory named '$WEBROOT' in $(pwd)" >&2
  exit 1
fi

LATEST_BACKUP="$(ls -1dt "${WEBROOT}".bak-* 2>/dev/null | head -n 1 || true)"

if [ -z "$LATEST_BACKUP" ]; then
  echo "Error: no backups found matching ${WEBROOT}.bak-*" >&2
  exit 1
fi

echo "Will roll back:"
echo "  Current: $WEBROOT"
echo "  Restore: $LATEST_BACKUP"
echo "  (current will be moved aside to ${WEBROOT}.broken-${TIMESTAMP})"
read -rp "Proceed? [y/N] " ans
[ "$ans" = "y" ] || [ "$ans" = "Y" ] || { echo "Cancelled."; exit 0; }

mv "$WEBROOT" "${WEBROOT}.broken-${TIMESTAMP}"
mv "$LATEST_BACKUP" "$WEBROOT"

echo "Rollback complete."
echo "Broken version preserved at: ${WEBROOT}.broken-${TIMESTAMP}"
