#!/usr/bin/env bash
# merge_composer.sh - safe merge of nested composer.json into kova/composer.json
# Usage: ./merge_composer.sh
set -euo pipefail

ROOT_DIR="."
OUT_DIR="kova"
OUT_COMPOSER="$OUT_DIR/composer.json"
REPORT="$OUT_DIR/merge-report.txt"

mkdir -p "$OUT_DIR"
rm -f "$OUT_COMPOSER" "$REPORT"

echo "Scanning for composer.json files (excluding vendor/)..."

# Find composer.json files excluding vendor directories
mapfile -t COMPOSERS < <(find "$ROOT_DIR" -type f -name "composer.json" ! -path "*/vendor/*" | sort)

if [ ${#COMPOSERS[@]} -eq 0 ]; then
  echo "No composer.json files found."
  exit 1
fi

echo "Found ${#COMPOSERS[@]} composer.json files:"
for f in "${COMPOSERS[@]}"; do
  echo "  - $f"
done

# Write the list of files to a temp file (one per line) to pass to PHP safely
TMP_LIST="$(mktemp)"
trap 'rm -f "$TMP_LIST"' EXIT
for f in "${COMPOSERS[@]}"; do
  printf '%s\n' "$f" >> "$TMP_LIST"
done

# Create a temp PHP script that reads the file list and merges composer.json contents
TMP_PHP="$(mktemp --suffix=.php)"
cat > "$TMP_PHP" <<'PHP'
<?php
if ($argc < 3) {
    fwrite(STDERR, "Usage: php merge_temp.php <file-list-path> <out-dir>\n");
    exit(2);
}
$fileList = $argv[1];
$outDir = rtrim($argv[2], "/");
$outComposer = $outDir . '/composer.json';
$reportFile = $outDir . '/merge-report.txt';

if (!file_exists($fileList)) {
    fwrite(STDERR, "File list not found: $fileList\n");
    exit(2);
}

$files = file($fileList, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
$merged = [
    "name" => null,
    "description" => null,
    "type" => "project",
    "require" => [],
    "require-dev" => [],
    "autoload" => [],
    "autoload-dev" => [],
    "minimum-stability" => "stable",
    "prefer-stable" => true
];

$conflicts = [];

function mergeAssoc(&$target, $source, $context, &$conflicts, $origin) {
    foreach ($source as $k => $v) {
        if (!array_key_exists($k, $target)) {
            $target[$k] = $v;
        } else {
            if ($target[$k] !== $v) {
                $conflicts[] = [
                    'context' => $context,
                    'package' => $k,
                    'first' => $target[$k],
                    'conflict' => $v,
                    'origin' => $origin
                ];
            }
        }
    }
}

function mergeAutoloadSection(&$mergedAutoload, $sourceAutoload, $origin, &$conflicts) {
    if (!is_array($mergedAutoload)) $mergedAutoload = [];
    if (isset($sourceAutoload['psr-4']) && is_array($sourceAutoload['psr-4'])) {
        if (!isset($mergedAutoload['psr-4'])) $mergedAutoload['psr-4'] = [];
        foreach ($sourceAutoload['psr-4'] as $ns => $path) {
            if (!isset($mergedAutoload['psr-4'][$ns])) {
                $mergedAutoload['psr-4'][$ns] = $path;
            } else {
                if ($mergedAutoload['psr-4'][$ns] !== $path) {
                    $conflicts[] = [
                        'context' => 'autoload.psr-4',
                        'namespace' => $ns,
                        'first' => $mergedAutoload['psr-4'][$ns],
                        'conflict' => $path,
                        'origin' => $origin
                    ];
                }
            }
        }
    }
    if (isset($sourceAutoload['classmap']) && is_array($sourceAutoload['classmap'])) {
        if (!isset($mergedAutoload['classmap'])) $mergedAutoload['classmap'] = [];
        foreach ($sourceAutoload['classmap'] as $entry) {
            if (!in_array($entry, $mergedAutoload['classmap'], true)) {
                $mergedAutoload['classmap'][] = $entry;
            }
        }
    }
    if (isset($sourceAutoload['files']) && is_array($sourceAutoload['files'])) {
        if (!isset($mergedAutoload['files'])) $mergedAutoload['files'] = [];
        foreach ($sourceAutoload['files'] as $entry) {
            if (!in_array($entry, $mergedAutoload['files'], true)) {
                $mergedAutoload['files'][] = $entry;
            }
        }
    }
    return $mergedAutoload;
}

foreach ($files as $f) {
    if (!file_exists($f)) continue;
    $json = json_decode(file_get_contents($f), true);
    if (!is_array($json)) continue;
    $origin = $f;
    if (empty($merged['name']) && !empty($json['name'])) $merged['name'] = $json['name'];
    if (empty($merged['description']) && !empty($json['description'])) $merged['description'] = $json['description'];

    if (!empty($json['require']) && is_array($json['require'])) {
        mergeAssoc($merged['require'], $json['require'], 'require', $conflicts, $origin);
    }
    if (!empty($json['require-dev']) && is_array($json['require-dev'])) {
        mergeAssoc($merged['require-dev'], $json['require-dev'], 'require-dev', $conflicts, $origin);
    }
    if (!empty($json['autoload']) && is_array($json['autoload'])) {
        $merged['autoload'] = mergeAutoloadSection($merged['autoload'], $json['autoload'], $origin, $conflicts);
    }
    if (!empty($json['autoload-dev']) && is_array($json['autoload-dev'])) {
        $merged['autoload-dev'] = mergeAutoloadSection($merged['autoload-dev'], $json['autoload-dev'], $origin, $conflicts);
    }
    if (!empty($json['minimum-stability']) && empty($merged['minimum-stability'])) {
        $merged['minimum-stability'] = $json['minimum-stability'];
    }
    if (isset($json['prefer-stable']) && empty($merged['prefer-stable'])) {
        $merged['prefer-stable'] = $json['prefer-stable'];
    }
}

// Build output
$out = [];
if (!empty($merged['name'])) $out['name'] = $merged['name'];
if (!empty($merged['description'])) $out['description'] = $merged['description'];
$out['type'] = $merged['type'];
if (!empty($merged['require'])) $out['require'] = $merged['require'];
if (!empty($merged['require-dev'])) $out['require-dev'] = $merged['require-dev'];
if (!empty($merged['autoload'])) {
    if (!empty($merged['autoload']['psr-4'])) $out['autoload']['psr-4'] = $merged['autoload']['psr-4'];
    if (!empty($merged['autoload']['classmap'])) $out['autoload']['classmap'] = array_values($merged['autoload']['classmap']);
    if (!empty($merged['autoload']['files'])) $out['autoload']['files'] = array_values($merged['autoload']['files']);
}
if (!empty($merged['autoload-dev'])) {
    if (!empty($merged['autoload-dev']['psr-4'])) $out['autoload-dev']['psr-4'] = $merged['autoload-dev']['psr-4'];
    if (!empty($merged['autoload-dev']['classmap'])) $out['autoload-dev']['classmap'] = array_values($merged['autoload-dev']['classmap']);
    if (!empty($merged['autoload-dev']['files'])) $out['autoload-dev']['files'] = array_values($merged['autoload-dev']['files']);
}
$out['minimum-stability'] = $merged['minimum-stability'];
$out['prefer-stable'] = $merged['prefer-stable'];

$encoded = json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
if ($encoded === false) {
    file_put_contents($reportFile, "ERROR: Failed to encode merged composer.json\n");
    echo "ERROR: Failed to encode merged composer.json\n";
    exit(2);
}

if (!is_dir($outDir)) mkdir($outDir, 0755, true);
file_put_contents($outComposer, $encoded . PHP_EOL);

// Report
$report = [];
$report[] = "Merge report generated on " . date(DATE_ATOM);
$report[] = "";
$report[] = "Source composer.json files:";
foreach ($files as $f) $report[] = " - $f";
$report[] = "";
$report[] = "Conflicts found: " . count($conflicts);
foreach ($conflicts as $c) {
    $report[] = "----------";
    $report[] = "Context: " . ($c['context'] ?? '(unknown)');
    if (isset($c['package'])) $report[] = "Package: " . $c['package'];
    if (isset($c['namespace'])) $report[] = "Namespace: " . $c['namespace'];
    $report[] = "First seen value: " . var_export($c['first'], true);
    $report[] = "Conflicting value: " . var_export($c['conflict'], true);
    $report[] = "Origin of conflicting value: " . ($c['origin'] ?? '(unknown)');
}
if (count($conflicts) === 0) $report[] = "No conflicts detected.";

file_put_contents($reportFile, implode(PHP_EOL, $report) . PHP_EOL);

echo "Merged composer.json written to: $outComposer\n";
echo "Merge report written to: $reportFile\n";
PHP

# Run the PHP merger using the temp file list
php "$TMP_PHP" "$TMP_LIST" "$OUT_DIR"

# Clean up temp PHP script
rm -f "$TMP_PHP"

echo ""
echo "Next steps:"
echo "  1) Inspect $OUT_COMPOSER and $REPORT"
echo "  2) Resolve any conflicts reported in $REPORT and edit $OUT_COMPOSER as needed"
echo "  3) When ready: cd $OUT_DIR && composer validate && composer install --no-dev --optimize-autoloader"
echo ""
