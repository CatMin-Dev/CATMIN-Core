#!/usr/bin/env bash

set -euo pipefail

targets=(app bootstrap config database modules routes tests)

while IFS= read -r -d '' file; do
  php -l "$file" >/dev/null
done < <(find "${targets[@]}" -name '*.php' -print0)

echo "PHP lint OK"
