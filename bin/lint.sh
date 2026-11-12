#!/bin/sh
# Lint de sintaxis de todo el codigo PHP del proyecto.
set -e
cd /app
FILES=$(find src config database public templates -name '*.php' 2>/dev/null)
COUNT=$(echo "$FILES" | wc -l)
echo "Archivos PHP: $COUNT"
FAIL=0
for f in $FILES; do
  if ! php -l "$f" >/dev/null 2>&1; then
    php -l "$f"
    FAIL=1
  fi
done
if [ "$FAIL" -eq 0 ]; then
  echo "SINTAXIS OK"
fi
