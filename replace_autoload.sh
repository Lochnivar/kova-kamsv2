#!/usr/bin/env bash
# Replace occurrences of require(...vendor/autoload.php) with require kova_path('app/bootstrap.php');
# Usage:
#   ./replace-autoloads.sh           # dry-run (shows files and diff)
#   ./replace-autoloads.sh --apply   # perform in-place edits with .bak backups
set -euo pipefail

APPLY=0
ROOT_DIR="."
BACKUP_EXT=".bak.replaceautoload"

if [[ "${1:-}" == "--apply" ]]; then
  APPLY=1
fi

# Find candidate files (php and common script extensions). Adjust as needed.
mapfile -t FILES < <(find "$ROOT_DIR" -type f \( -name "*.php" -o -name "*.inc" -o -name "*.phtml" \) -print)

if [ ${#FILES[@]} -eq 0 ]; then
  echo "No candidate files found."
  exit 0
fi

# Perl regex to match common require variants that point at a vendor/autoload.php one level up.
# Variants covered:
#   require '../vendor/autoload.php';
#   require "../vendor/autoload.php";
#   require(__DIR__ . '/../vendor/autoload.php');
#   require __DIR__ . "/../vendor/autoload.php";
#   require dirname(__DIR__) . '/vendor/autoload.php';
# The replacement is a single token: require kova_path('app/bootstrap.php');
perl_expr='
  # single-line mode
  s{
    (                               # $1 = full match leading code (unused)
      \brequire\b \s* \(? \s*       # require ( optional paren
      (?:                           # begin group for path expression
        __DIR__ \s* \. \s*     ["'"'"'] \s* \.{2} \/?vendor\/autoload\.php ["'"'"']   | # __DIR__ . "../vendor/autoload.php"
        __DIR__ \s* \. \s*     ["'"'"'] \s* \.{2} \/?vendor\/autoload\.php ["'"'"']   | # duplicate form safe
        ["'"'"'] \.{2}\/vendor\/autoload\.php ["'"'"']                                | # "../vendor/autoload.php"
        ["'"'"'] \.{2}vendor\/autoload\.php ["'"'"']                                  | # "..vendor/autoload.php" (less common)
        dirname\s*\(\s*__DIR__\s*\) \s* \. \s* ["'"'"'] \/?vendor\/autoload\.php ["'"'"']  # dirname(__DIR__) . '/vendor/autoload.php'
      )
      \s* \)? \s* ;                   # optional ) and semicolon
    )
  }{require kova_path('\''app/bootstrap.php'\'');}gix
'

# Show diffs (dry-run)
for f in "${FILES[@]}"; do
  if perl -0777 -ne "$perl_expr print if 0" "$f" 2>/dev/null | grep -q .; then
    # no-op; this branch left for clarity
    :
  fi

  # Test whether the file contains a vendor/autoload require we care about
  if grep -E --line-number -n "require[^(]*(['\"]\.\./vendor/autoload\.php['\"])|__DIR__.*vendor\/autoload|dirname\(__DIR__\).*vendor\/autoload" "$f" >/dev/null 2>&1; then
    if [ "$APPLY" -eq 0 ]; then
      echo "---- DRY-RUN: matches in $f ----"
      # show contextual grep lines
      grep -n -n --color=always -E "require[^(]*(['\"]\.\./vendor/autoload\.php['\"])|__DIR__.*vendor\/autoload|dirname\(__DIR__\).*vendor\/autoload" "$f" || true
      echo
      # show a unified diff of the proposed change (uses perl to produce in-memory replacement)
      awk '{print NR ":" $0}' "$f" > /tmp/replace-autoload.$$.orig
      perl -0777 -pe "$perl_expr" "$f" > /tmp/replace-autoload.$$.new
      if ! diff -u /tmp/replace-autoload.$$.orig /tmp/replace-autoload.$$.new >/dev/null 2>&1; then
        echo "---- proposed diff for $f ----"
        diff -u /tmp/replace-autoload.$$.orig /tmp/replace-autoload.$$.new || true
        echo
      fi
      rm -f /tmp/replace-autoload.$$.orig /tmp/replace-autoload.$$.new
    else
      # Backup then replace in-place using perl, preserving metadata
      cp -p "$f" "$f$BACKUP_EXT"
      perl -0777 -pe "$perl_expr" -i "$f"
      echo "Edited: $f (backup: $f$BACKUP_EXT)"
    fi
  fi
done

if [ "$APPLY" -eq 0 ]; then
  echo "Dry-run complete. Re-run with --apply to perform in-place edits."
else
  echo "Replacement complete. Review .bak files for backups (suffix: $BACKUP_EXT)."
fi

exit 0
