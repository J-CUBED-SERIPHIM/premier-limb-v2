#!/usr/bin/env bash
# Deploy the "Double Board-Certified" credential row to prod (posts 166 home, 169 dr-basmajian).
# Needs the OpenVPN tunnel up. Backs up both posts, splices only the credential row, verifies live HTML.
set -euo pipefail
HERE="$(cd "$(dirname "$0")" && pwd)"
ROOT=/var/www/premierlimblengthening.com
scp -q -o BatchMode=yes "$HERE/deploy-credentials.php" pll-vault:/tmp/deploy-credentials.php
ssh -o BatchMode=yes pll-vault "set -euo pipefail; cd $ROOT
  [ \"\$(wp post list --post_type=wp_template_part --format=count)\" = 0 ] || { echo 'ABORT: template parts exist'; exit 1; }
  D=\$HOME/pll-backups/credentials-\$(date +%Y%m%d-%H%M%S); mkdir -p \$D
  for id in 166 169; do wp post get \$id --field=post_content > \$D/post-\$id.html; done
  echo BACKUP=\$D
  wp eval-file --user=1 /tmp/deploy-credentials.php apply
  wp cache flush
  echo \"ROLLBACK: for id in 166 169; do wp post update \\\$id \$D/post-\\\$id.html; done\"" 2>&1 | grep -v '^\*\*'
echo "--- verify live"
for u in https://premierlimblengthening.com/ https://premierlimblengthening.com/dr-basmajian/; do
  H=$(curl -fsSL "$u")
  if grep -q 'Double Board-Certified' <<<"$H" && grep -q 'Fellow, American Academy of Orthopaedic Surgeons (FAAOS)' <<<"$H" && ! grep -q 'Orthopaedic Surgeons (2014)' <<<"$H"; then
    echo "OK   $u"
  else
    echo "FAIL $u (may be page cache; re-check)"
  fi
done
