#!/usr/bin/env bash
# Production deploy, 2026-09-07.
#
# Ships the owner-approved changes only:
#   - paa.php            : removes the "$1,200/month" claim from the FAQ schema
#   - prod-apply script   : applies the pricing copy + cannibalization link
#                           changes to the live page content
#
# Deliberately does NOT deploy setup.php, schema.php, meta.php or the theme
# patterns. Those files are far ahead of production and carry other people's
# in-flight work that has not been reviewed. See the notes at the end.
#
# Safe to re-run: the PHP migration checks for its own result before changing
# anything, so a second run reports "already" rather than double-applying.
#
# Usage:  bash wordpress/scripts/deploy-20260907.sh
# Needs:  the HIPAA Vault VPN tunnel up (ssh pll-vault must succeed).

set -euo pipefail

HOST=pll-vault
DOC=/var/www/premierlimblengthening.com
STAMP=$(date -u +%Y%m%dT%H%M%SZ)
BACKUP=/home/rjimenez/deploy-backups/20260907
HERE="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

say() { printf '\n\033[1m== %s\033[0m\n' "$*"; }

say "0. Preflight"
ssh -o BatchMode=yes "$HOST" "test -d $DOC" || { echo "cannot reach $DOC"; exit 1; }
echo "   reachable, doc root present"

say "1. Backup (database + the file we replace)"
ssh -o BatchMode=yes "$HOST" "
  mkdir -p $BACKUP
  wp db export $BACKUP/db-$STAMP.sql --allow-root --path=$DOC >/dev/null
  cp $DOC/wp-content/plugins/pll-seo/includes/data/paa.php $BACKUP/paa.php.$STAMP.bak
  ls -la $BACKUP/db-$STAMP.sql $BACKUP/paa.php.$STAMP.bak
"

say "2. Upload paa.php + the migration, and syntax-check BOTH before use"
tr -d '\r' < "$HERE/wp-content/plugins/pll-seo/includes/data/paa.php" \
  | ssh -o BatchMode=yes "$HOST" "cat > /tmp/paa.new.php && php -l /tmp/paa.new.php"
tr -d '\r' < "$HERE/scripts/prod-apply-20260907.php" \
  | ssh -o BatchMode=yes "$HOST" "cat > /tmp/prod-apply.php && php -l /tmp/prod-apply.php"

say "3. Replace paa.php in place (cat > preserves the inode)"
ssh -o BatchMode=yes "$HOST" "
  T=$DOC/wp-content/plugins/pll-seo/includes/data/paa.php
  before=\$(stat -c %i \$T)
  cat /tmp/paa.new.php > \$T
  after=\$(stat -c %i \$T)
  php -l \$T
  echo \"   inode \$before -> \$after\"
  [ \"\$before\" = \"\$after\" ] && echo '   inode preserved' || echo '   WARNING: inode changed'
"

say "4. Apply the content changes"
ssh -o BatchMode=yes "$HOST" "cd $DOC && wp eval-file /tmp/prod-apply.php --allow-root --path=$DOC"

say "5. Verify against the live site"
ssh -o BatchMode=yes "$HOST" "rm -f /tmp/paa.new.php /tmp/prod-apply.php"

fail=0
check() { # url, pattern, expectation(gone|present), label
  n=$(curl -s "$1" | grep -c "$2" || true)
  if [ "$3" = "gone" ] && [ "$n" -eq 0 ]; then echo "   OK   $4"
  elif [ "$3" = "present" ] && [ "$n" -gt 0 ]; then echo "   OK   $4"
  else echo "   FAIL $4 (found $n)"; fail=1; fi
}
P=https://premierlimblengthening.com
check "$P/limb-lengthening-pricing-options/" '\$1,200'                  gone    'pricing: $1,200 tile gone'
check "$P/limb-lengthening-pricing-options/" 'Indicative monthly'       gone    'pricing: 0%/monthly tiles gone'
check "$P/limb-lengthening-pricing-options/" 'priced like a partner'    gone    'pricing: old h1 gone'
check "$P/limb-lengthening-pricing-options/" 'PRECICE MAX cost'         present 'pricing: new h2 live'
check "$P/limb-lengthening-pricing-options/" 'PRECICE 4th Generation'   gone    'pricing: 4th Generation wording gone'
check "$P/limb-lengthening-pricing-options/" '4 PRECICE MAX internal'   present 'pricing: $195,000 says 4 PRECICE MAX'
check "$P/limb-lengthening-pricing-options/" '2 PRECICE 2 internal'     present 'pricing: PRECICE 2 tiers untouched'
check "$P/"                                  '1,200'                    gone    'home: no payment figure'
check "$P/"                                  'PRECICE MAX internal'     present 'home: MAX naming live'
check "$P/is-limb-lengthening-covered-by-insurance/" '1,200'            gone    'insurance: claim gone from copy AND schema'
check "$P/is-limb-lengthening-covered-by-insurance/" 'href="/your-surgery/"' present 'insurance: head-term anchor live'

say "Result"
if [ "$fail" -eq 0 ]; then
  echo "   All checks passed."
else
  echo "   SOME CHECKS FAILED. Roll back with:"
  echo "   ssh $HOST \"cp $BACKUP/paa.php.$STAMP.bak $DOC/wp-content/plugins/pll-seo/includes/data/paa.php\""
  echo "   ssh $HOST \"wp db import $BACKUP/db-$STAMP.sql --allow-root --path=$DOC\""
  exit 1
fi

cat <<'NOTES'

Deliberately NOT shipped, and why:
  schema.php  A6's founder/entity line is 2 lines, but that file also carries an
              unverified FAQ-from-visible-blocks feature from another workstream.
  meta.php    Carries the booking/contact split metadata and the price-anchor
              flag. Note the "From $75,500" title is ALREADY live via post meta,
              so this file is not what put it there.
  setup.php   Production's copy is from 2026-07-28 and ~620 lines behind. Running
              the current one would also seed marketing-sprint drafts and other
              out-of-scope work. That is why the migration above is standalone.
  patterns/   Only affect newly created pages, so they change nothing on pages
              that already exist. No rush, but they should follow eventually so
              a rebuild does not reintroduce the old copy.

Still outstanding on the audit: A1 (Gus rewrites the pain hub page), A3 (blocked
on which page owns "surgery to get taller"), A4 (needs the fixation content merge
plus medical review before its redirect is switched on), A8 (owner disagreed with
the finding).
NOTES
