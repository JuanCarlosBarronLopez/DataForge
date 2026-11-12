#!/bin/sh
# ============================================
# DataForge - Comprobaciones de higiene del repositorio
# ============================================
# Se ejecuta dentro del contenedor de desarrollo:
#   docker run --rm -v "$(pwd)":/app -w /app dataforge-dev sh bin/higiene.sh

set -e
cd /app

echo "=== .env versionado (no debe aparecer) ==="
if git ls-files --error-unmatch .env >/dev/null 2>&1; then
    echo "PELIGRO: el archivo .env esta versionado"
    exit 1
fi
echo "OK: .env no esta versionado"

echo "=== registros o archivos generados versionados ==="
GENERATED=$(git ls-files | grep -E '^storage/(logs|cache|uploads|framework)/' | grep -vE '\.(gitignore|gitkeep)$' || true)
if [ -n "$GENERATED" ]; then
    echo "PELIGRO: hay archivos generados versionados:"
    echo "$GENERATED"
    exit 1
fi
echo "OK: no hay archivos generados versionados"

echo "=== correos electronicos ==="
git grep -ohIE '[A-Za-z0-9._%-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}' -- ':!composer.lock' | sort -u || true

echo "=== referencias a la empresa ficticia o a OMNARA ==="
git grep -niIE 'raju|omnara' -- ':!composer.lock' || true

echo "=== archivos de mas de 512 KB ==="
git ls-files | while read -r file; do
    if [ -f "$file" ]; then
        size=$(wc -c < "$file")
        if [ "$size" -gt 524288 ]; then
            echo "$file : $((size / 1024)) KB"
        fi
    fi
done
