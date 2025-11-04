#!/usr/bin/env bash
set -euo pipefail

# namespace_migrate.sh
# Scans for legacy namespace/use occurrences and optionally applies a codemod.
# Operates on both unified/ and kcm/ directories.
#
# Usage:
#   bash scripts/namespace_migrate.sh            # dry-run (preview)
#   bash scripts/namespace_migrate.sh --apply    # apply changes (creates .bak backups)
#   bash scripts/namespace_migrate.sh --apply --alias --commit --cleanup
#   bash scripts/namespace_migrate.sh --help

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# Defaults
DRY_RUN=1
ADD_ALIAS=0
CLEANUP_BACKUPS=0
COMMIT_CHANGES=0
QUIET=0
BACKUP_EXT=".bak"
PREVIEW_LIMIT=50

usage(){
  cat <<EOF
Usage: $0 [--apply] [--alias] [--cleanup] [--commit] [--brief] [--help]
  --apply    Apply changes (default is dry-run)
  --alias    Add temporary legacy alias shim and add to composer.json autoload.files
  --cleanup  Remove backup files (*.bak) after successful apply
  --commit   Create a WIP git commit after apply
  --brief    Minimize preview output in dry-run (prints filenames only)
  --help     Show this help
EOF
  exit 1
}

while [ $# -gt 0 ]; do
  case "$1" in
    --apply) DRY_RUN=0 ;;
    --alias) ADD_ALIAS=1 ;;
    --cleanup) CLEANUP_BACKUPS=1 ;;
    --commit) COMMIT_CHANGES=1 ;;
    --brief) QUIET=1 ;;
    -h|--help) usage ;;
    *) echo "Unknown arg: $1" >&2; usage ;;
  esac
  shift
done

log(){ printf '[%s] %s\n' "$(date -u +"%Y-%m-%dT%H:%M:%SZ")" "$*"; }

# Requirements
for cmd in grep perl sed cut sort uniq xargs; do
  if ! command -v "$cmd" >/dev/null 2>&1; then
    echo "ERROR: required command '$cmd' not found in PATH" >&2
    exit 2
  fi
done

log "Starting namespace migration script"
log "Project root: $PROJECT_ROOT"
if [ $DRY_RUN -eq 1 ]; then
  log "Mode: DRY-RUN (no files will be changed). Use --apply to modify files."
else
  log "Mode: APPLY (files WILL be modified; backups saved with extension $BACKUP_EXT)."
fi

# Directories to scan
SCAN_DIRS=(unified kcm)
# Allow directories to be absent without failing
GREP_PATHS=()
for d in "${SCAN_DIRS[@]}"; do
  if [ -d "$d" ]; then GREP_PATHS+=("$d"); fi
done

if [ ${#GREP_PATHS[@]} -eq 0 ]; then
  log "No scan directories (unified/ or kcm/) found in repo. Exiting."
  exit 0
fi

# Candidate detection patterns (broadened)
PATTERN='^namespace\s+Kova\\Unified|use\s+Kova\\Unified|Kova\\\Unified\\\\'
# Collect unique files
mapfile -t CANDIDATE_FILES < <(grep -R --line-number -E "$PATTERN" "${GREP_PATHS[@]}" 2>/dev/null | cut -d: -f1 | sort -u)

CANDIDATE_COUNT=${#CANDIDATE_FILES[@]}
log "Found $CANDIDATE_COUNT file(s) matching legacy patterns in: ${GREP_PATHS[*]}"

if [ $CANDIDATE_COUNT -eq 0 ]; then
  # Try a broader case-insensitive search in those dirs (helpful for variant spellings)
  mapfile -t BROAD_FILES < <(grep -R --line-number -i "Kova.*Unified" "${GREP_PATHS[@]}" 2>/dev/null | cut -d: -f1 | sort -u)
  if [ ${#BROAD_FILES[@]} -gt 0 ]; then
    log "No exact matches, but found ${#BROAD_FILES[@]} case-insensitive occurrences. Listing files:"
    for f in "${BROAD_FILES[@]}"; do echo "$f"; done
    log "If these are legacy refs consider running with --apply after review."
    exit 0
  fi

  log "No legacy namespace/use declarations found. Exiting."
  exit 0
fi

# Dry-run preview
if [ $DRY_RUN -eq 1 ]; then
  if [ $QUIET -eq 1 ]; then
    log "Dry-run (--brief): listing candidate files only"
    for f in "${CANDIDATE_FILES[@]}"; do echo "$f"; done
    log "To apply changes: $0 --apply"
    exit 0
  fi

  log "Previewing up to $PREVIEW_LIMIT files. For each file: original top lines then transformed preview."
  cnt=0
  for f in "${CANDIDATE_FILES[@]}"; do
    cnt=$((cnt+1))
    echo "=== [$cnt/$CANDIDATE_COUNT] $f ==="
    echo "--- original (top 80 lines) ---"
    sed -n '1,80p' "$f" || true
    echo "--- transformed preview (top 80 lines) ---"
    perl -0777 -pe "s/^namespace\s+Kova\\\\Unified(\\\\?[^;]*)?;/namespace Kova\\\\Kams\\\\Unified\$1;/mg; s/use\s+Kova\\\\Unified\\\\/use Kova\\\\Kams\\\\Unified\\\\/g" "$f" | sed -n '1,80p' || true
    echo ""
    if [ $cnt -ge $PREVIEW_LIMIT ]; then
      log "Preview limit reached ($PREVIEW_LIMIT). To see all previews increase PREVIEW_LIMIT or run with --apply to patch files."
      break
    fi
  done
  log "Dry-run preview complete. Run with --apply to perform replacements."
  exit 0
fi

# APPLY mode: perform changes
PATCHED_NS=0
PATCHED_USES=0
PATCHED_KCM=0
ERRORS=0

for f in "${CANDIDATE_FILES[@]}"; do
  changed=0
  # Patch namespace declarations
  if grep -qE "^namespace\s+Kova\\Unified" "$f"; then
    cp -p "$f" "$f$BACKUP_EXT"
    if perl -0777 -pe "s/^namespace\s+Kova\\\\Unified(\\\\?[^;]*)?;/namespace Kova\\\\Kams\\\\Unified\$1;/mg" -i "$f"; then
      log "Patched namespace in: $f"
      PATCHED_NS=$((PATCHED_NS+1)); changed=1
    else
      log "ERROR patching namespace in: $f"; ERRORS=$((ERRORS+1))
    fi
  fi

  # Patch use statements
  if grep -qE "use\s+Kova\\Unified" "$f"; then
    # ensure backup exists
    if [ ! -f "$f$BACKUP_EXT" ]; then cp -p "$f" "$f$BACKUP_EXT"; fi
    if perl -0777 -pe "s/use\s+Kova\\\\Unified\\\\/use Kova\\\\Kams\\\\Unified\\\\/g" -i "$f"; then
      log "Patched use statements in: $f"
      PATCHED_USES=$((PATCHED_USES+1)); changed=1
    else
      log "ERROR patching use statements in: $f"; ERRORS=$((ERRORS+1))
    fi
  fi

  # If file had other legacy textual occurrences, optionally show a summary (non-destructive)
  if [ $changed -eq 1 ]; then
    # show the top 6 lines of file after change for quick verification
    echo ">>> After change (top lines) $f"
    sed -n '1,12p' "$f" || true
  fi
done

log "Namespace/use replacements done. Namespaces patched: $PATCHED_NS; files with uses patched: $PATCHED_USES"

# Attempt safe kcm dispatcher fix if present and obviously malformed
KCM_DISPATCHER="kcm/src/Dispatcher.php"
if [ -f "$KCM_DISPATCHER" ]; then
  if grep -qE "^namespace\s+Kova\\\Kcm|KCM\\\src|namespace\s+Kova\\\Kcm\\\KCM" "$KCM_DISPATCHER" 2>/dev/null || grep -q "Kova\\\\Unified" "$KCM_DISPATCHER" 2>/dev/null; then
    log "kcm dispatcher appears to have a non-canonical namespace; creating backup and applying safe patch to 'namespace Kova\\Kams\\KCM;'"
    cp -p "$KCM_DISPATCHER" "$KCM_DISPATCHER$BACKUP_EXT"
    if perl -0777 -pe "s/^namespace\s+[^\n;]+;/namespace Kova\\\\Kams\\\\KCM;/m" -i "$KCM_DISPATCHER"; then
      log "Patched $KCM_DISPATCHER to namespace Kova\\Kams\\KCM"
      PATCHED_KCM=1
    else
      log "ERROR patching $KCM_DISPATCHER"
      ERRORS=$((ERRORS+1))
    fi
  else
    log "$KCM_DISPATCHER appears canonical or requires manual review; no automatic change applied."
  fi
else
  log "$KCM_DISPATCHER not found; skipping dispatcher check."
fi

# Add legacy alias shim if requested
if [ $ADD_ALIAS -eq 1 ]; then
  SHIM="scripts/legacy_aliases.php"
  log "Adding legacy alias shim: $SHIM"
  mkdir -p scripts
  cat > "$SHIM" <<'PHP'
<?php
// Temporary compatibility shims mapping old FQCNs to new canonical FQCNs.
// Add entries as needed; remove after migration completes.
if (class_exists(\Kova\Kams\Unified\Dispatcher::class, false) && !class_exists(\Kova\Unified\Dispatcher::class, false)) {
    class_alias(\Kova\Kams\Unified\Dispatcher::class, \Kova\Unified\Dispatcher::class);
}
PHP
  if ! grep -q "\"scripts/legacy_aliases.php\"" composer.json 2>/dev/null; then
    if command -v jq >/dev/null 2>&1; then
      tmp=$(mktemp)
      jq '.autoload.files = (.autoload.files // []) + ["scripts/legacy_aliases.php"]' composer.json > "$tmp" && mv "$tmp" composer.json
      log "Added scripts/legacy_aliases.php to composer.json autoload.files using jq"
    else
      log "jq not available; please add scripts/legacy_aliases.php to composer.json autoload.files manually"
    fi
  else
    log "composer.json already contains scripts/legacy_aliases.php"
  fi
fi

# Rebuild Composer autoload if available
if command -v composer >/dev/null 2>&1; then
  log "Running composer dump-autoload --no-dev (COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NON_INTERACTIVE=1)"
  if COMPOSER_ALLOW_SUPERUSER=1 COMPOSER_NON_INTERACTIVE=1 composer dump-autoload --no-dev 2>&1 | sed -n '1,200p'; then
    log "composer dump-autoload completed"
  else
    log "composer dump-autoload returned non-zero; inspect output above"
  fi
else
  log "composer not found; skipping autoload rebuild. Run composer dump-autoload manually if needed."
fi

# Autoload smoke tests (best-effort)
log "Running autoload smoke tests (best-effort). Output:"
php -r 'try{ require "app/bootstrap.php"; $c=["Kova\\\\Kams\\\\Unified\\\\Dispatcher","Kova\\\\Kams\\\\Unified\\\\Modules\\\\Home\\\\Home"]; foreach($c as $cl){ printf("%s => %s\n",$cl,var_export(class_exists($cl,true),true)); } } catch(Throwable $e){ fwrite(STDERR,"Bootstrap error: ".$e->getMessage()."\n"); exit(1);} ' || log "autoload smoke tests exited non-zero"

# Optional commit
if [ $COMMIT_CHANGES -eq 1 ]; then
  if git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    git add -A
    git commit -m "WIP: migrate legacy Kova\\Unified to Kova\\Kams\\Unified (automated codemod)" || log "git commit returned non-zero (maybe nothing to commit)"
    log "Committed migration changes"
  else
    log "Not in a git repo; skipping commit"
  fi
fi

# Optional cleanup backups
if [ $CLEANUP_BACKUPS -eq 1 ]; then
  log "Cleaning up backup files (*$BACKUP_EXT)"
  find unified kcm -type f -name "*$BACKUP_EXT" -print -delete || log "Error deleting backups (check permissions)"
  log "Backup cleanup complete"
fi

# Final summary
log "Migration summary:"
log "  Candidate files scanned: $CANDIDATE_COUNT"
log "  Namespaces patched: $PATCHED_NS"
log "  Files with use replacements: $PATCHED_USES"
log "  kcm dispatcher patched: $PATCHED_KCM"
log "  Errors encountered: $ERRORS"
if [ $ADD_ALIAS -eq 1 ]; then log "  Legacy alias shim: scripts/legacy_aliases.php (added)"; fi
if [ $COMMIT_CHANGES -eq 1 ]; then log "  Changes committed (if git present)"; fi

log "Done. If anything is wrong, restore backups (*.bak) or revert the commit created."
exit 0
