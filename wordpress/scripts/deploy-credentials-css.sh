#!/usr/bin/env bash
# Bridge CSS for the credential bullets: prod's compiled pll.css was purged without
# list-disc / col-start-1 / sm:col-start-2, and resets ul to list-style:none.
# Appends a marked block to the live pll.css (idempotent, backed up outside the web root).
# Run as: bash -l wordpress/scripts/deploy-credentials-css.sh   (OpenVPN up)
set -euo pipefail
CSS="$(mktemp)"
cat > "$CSS" <<'EOF'
/*! pll-cred-list:begin */
ul.wp-block-list.list-disc{list-style:disc}
ul.wp-block-list.list-disc>li+li{margin-top:.25rem}
ul.wp-block-list.col-start-1{grid-column-start:1}
@media(min-width:640px){ul.wp-block-list.sm\:col-start-2{grid-column-start:2}}
/*! pll-cred-list:end */
EOF
scp -q -o BatchMode=yes "$CSS" pll-vault:/tmp/pll-cred-list.css
ssh -o BatchMode=yes pll-vault 'set -euo pipefail
  F=/var/www/premierlimblengthening.com/wp-content/themes/pll-editorial/assets/css/pll.css
  if grep -q "pll-cred-list:begin" "$F"; then echo "already applied, nothing to do"; exit 0; fi
  D=$HOME/pll-backups/credentials-css-$(date +%Y%m%d-%H%M%S); mkdir -p $D; cp -p "$F" $D/pll.css
  echo BACKUP=$D
  tr -d "\r" < /tmp/pll-cred-list.css | { printf "\n"; cat; } >> "$F"
  echo "ROLLBACK: cat $D/pll.css > $F"
  echo appended' 2>&1 | grep -v '^\*\*'
echo "--- verify live"
sleep 2
U=$(curl -fsSL https://premierlimblengthening.com/dr-basmajian/ | grep -o 'https://[^"]*pll\.css[^"]*' | head -1)
curl -fsSL "$U" | grep -q 'pll-cred-list:begin' && echo "OK   css live: $U" || echo "FAIL css not live yet (cache?)"
