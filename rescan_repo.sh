#!/usr/bin/env bash
set -euo pipefail

# Resolve script directory and project root
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(cd "$SCRIPT_DIR/.." && pwd)"
cd "$PROJECT_ROOT"

# Flags
BRIEF=0
for arg in "$@"; do
  case "$arg" in
    --brief) BRIEF=1 ;;
    -h|--help)
      echo "Usage: $0 [--brief]"
      echo "  --brief    Skip composer --optimize and other slow steps"
      exit 0
      ;;
  esac
done

# Report paths
OUT_DIR="build/scan-report"
mkdir -p "$OUT_DIR"
REPORT="$OUT_DIR/report.txt"
PSR4_FILE="$OUT_DIR/psr4.json"
CLASSMAP_FILE="$OUT_DIR/classmap.json"
NAMESPACES="$OUT_DIR/namespaces.txt"
LEGACY_REFS="$OUT_DIR/legacy_refs.txt"

# Helper to log with timestamp
log() {
  local ts msg
  ts="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
  msg="$*"
  echo "[$ts] $msg" | tee -a "$REPORT"
}

# Ensure we start fresh for this run but keep previous reports
if [ -f "$REPORT" ]; then mv "$REPORT" "$REPORT.$(date -u +%s).bak"; fi
echo "Scan generated on: $(date -u)" > "$REPORT"
echo "" >> "$REPORT"

# Trap to exit cleanly if user interrupts
trap 'log "Interrupted; exiting early"; exit 1' INT TERM

log "Starting repository scan at $PROJECT_ROOT"

log "Step 1: Git branch & last commit"
{
  git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "no-git"
  git --no-pager log -1 --pretty=format:"%h %ad %an %s" --date=iso 2>/dev/null || true
} | sed -n '1,200p' >> "$REPORT"
log "Finished Step 1"

log "Step 2: composer.json top-level summary"
jq -r '{name: .name, description: .description, license: .license, autoload: .autoload}' composer.json 2>/dev/null >> "$REPORT" || sed -n '1,120p' composer.json >> "$REPORT"
log "Finished Step 2"

log "Step 3: composer validate (no network)"
composer validate --no-check-publish --strict 2>&1 | sed -n '1,200p' >> "$REPORT" || true
log "Finished Step 3"

if [ "$BRIEF" -eq 0 ]; then
  log "Step 4: composer dump-autoload --optimize (this may be slow)"
  # Run without blocking output and capture exit code
  if composer dump-autoload --optimize --no-dev 2>&1 | sed -n '1,200p' >> "$REPORT"; then
    log "composer dump-autoload finished OK"
  else
    log "composer dump-autoload returned non-zero (captured above). Continuing scan."
  fi
else
  log "Step 4: SKIPPED composer dump-autoload due to --brief"
fi

log "Step 5: capture Composer autoload tables (if available)"
php -r 'echo json_encode(require "vendor/composer/autoload_psr4.php", JSON_PRETTY_PRINT), PHP_EOL;' > "$PSR4_FILE" 2>/dev/null || echo "{}" > "$PSR4_FILE"
php -r 'echo json_encode(require "vendor/composer/autoload_classmap.php", JSON_PRETTY_PRINT), PHP_EOL;' > "$CLASSMAP_FILE" 2>/dev/null || echo "{}" > "$CLASSMAP_FILE"
echo "PSR-4 mappings written to: $PSR4_FILE" >> "$REPORT"
echo "Classmap written to: $CLASSMAP_FILE" >> "$REPORT"
log "Finished Step 5"

log "Step 6: Namespace declarations (sample)"
grep -R --line-number "^namespace " unified KCM app 2>/dev/null | sed -n '1,100p' > "$NAMESPACES" || true
if [ -s "$NAMESPACES" ]; then
  head -n 200 "$NAMESPACES" >> "$REPORT"
else
  echo "(no namespace declarations found in unified/KCM/app search paths)" >> "$REPORT"
fi
log "Finished Step 6"

log "Step 7: Legacy FQCN references scan"
grep -R --line-number -E "Kova\\\\Unified|Kova\\\\Kams\\\\Unified|KCM\\\\|\\\\bUnified\\\\" . 2>/dev/null | sed -n '1,200p' > "$LEGACY_REFS" || true
if [ -s "$LEGACY_REFS" ]; then
  head -n 200 "$LEGACY_REFS" >> "$REPORT"
else
  echo "(no legacy FQCN text matches found in repo root scan)" >> "$REPORT"
fi
log "Finished Step 7"

log "Step 8: Autoload smoke tests"
php -r 'require "app/bootstrap.php"; $candidates = ["Kova\\\\Kams\\\\Unified\\\\Dispatcher", "Kova\\\\Kams\\\\Unified\\\\Modules\\\\Home\\\\Home"]; foreach($candidates as $c){ printf("%s => %s\n",$c, var_export(class_exists($c,true), true)); }' 2>&1 | sed -n '1,120p' >> "$REPORT" || true
log "Finished Step 8"

log "Step 9: Autoload path sanity checks"
php -r '
$j = json_decode(file_get_contents("composer.json"), true);
$paths = [];
if (isset($j["autoload"]["psr-4"])) foreach($j["autoload"]["psr-4"] as $ns=>$p) $paths[]=$p;
if (isset($j["autoload"]["classmap"])) foreach($j["autoload"]["classmap"] as $p) $paths[]=$p;
if (isset($j["autoload"]["files"])) foreach($j["autoload"]["files"] as $p) $paths[]=$p;
$paths=array_values(array_filter(array_unique($paths)));
foreach($paths as $p){
  $exists = file_exists($p) ? "OK" : "MISSING";
  echo "$p -> $exists\n";
}
' >> "$REPORT" 2>/dev/null || true
log "Finished Step 9"

log "Scan complete. Files written to $OUT_DIR/"
echo "" >> "$REPORT"
echo "Scan finished: $(date -u)" >> "$REPORT"
log "All done"
