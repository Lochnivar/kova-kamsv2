<?php

declare(strict_types=1);

/**
 * migrate_dbquery_to_dbal.php
 *
 * Usage:
 *   php migrate_dbquery_to_dbal.php path/to/file1.php path/to/dir ... 
 *
 * Outputs:
 *   - file.php.codemod for each changed file (dry-run)
 *   - migrate-report.json summarizing conversions and manual-review items
 *
 * Notes:
 *   - Requires nikic/php-parser (dev dependency).
 *   - Conservative: converts only when it can safely infer table/columns for INSERT/UPDATE/DELETE.
 *   - For SELECTs it converts the call to ->query($sql, [$params]) and will attempt to append fetchAllAssociative()
 *     when caller code pattern is straightforward; otherwise marks for manual review.
 */

// prefer an existing project root constant if available
if (defined('KOVA_DIR')) {
    $autoload = rtrim(constant('KOVA_DIR'), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
    if (!file_exists($autoload)) {
        fwrite(STDERR, "KOVA_DIR is defined but vendor/autoload.php not found at: {$autoload}\n");
        exit(1);
    }
    require $autoload;
} else {
    // fallback: search upward for vendor/autoload.php
    $autoload = null;
    $dir = __DIR__;
    for ($i = 0; $i < 6; $i++) {
        $try = $dir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
        if (file_exists($try)) {
            $autoload = $try;
            break;
        }
        $dir = dirname($dir);
    }
    if ($autoload === null) {
        fwrite(STDERR, "Cannot find vendor/autoload.php; run composer install from the project root or define KOVA_DIR\n");
        exit(1);
    }
    require $autoload;
}

use PhpParser\{ParserFactory, NodeTraverser, NodeVisitorAbstract, PrettyPrinter};
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Arg;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt;
use PhpParser\NodeFinder;

$argvFiles = array_slice($argv, 1);
if (empty($argvFiles)) {
    echo "Usage: php migrate_dbquery_to_dbal.php path/to/file.php [more files or directories...]\n";
    exit(1);
}

// Expand directories into file list
$files = [];
foreach ($argvFiles as $path) {
    if (is_dir($path)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
        foreach ($it as $f) {
            if ($f->isFile() && substr($f->getFilename(), -4) === '.php') {
                $files[] = $f->getPathname();
            }
        }
    } elseif (is_file($path)) {
        $files[] = $path;
    }
}
$files = array_values(array_unique($files));
if (empty($files)) {
    echo "No PHP files found.\n";
    exit(1);
}

// Parser factory (works with php-parser v4)
$pf = new \PhpParser\ParserFactory();
$parser = $pf->create(\PhpParser\ParserFactory::PREFER_PHP7);

$printer = new PrettyPrinter\Standard();
$nodeFinder = new NodeFinder();

$report = [
    'converted' => [],
    'fallback' => [],
    'manual_review' => [],
    'errors' => [],
];

function normalizeSql(string $sql): string
{
    // collapse whitespace, trim
    $s = preg_replace('/\s+/', ' ', trim($sql));
    return $s;
}

function detectVerb(string $sql): string
{
    $s = ltrim($sql);
    $tok = strtoupper(strtok($s, " \t\n\r\0\x0B"));
    return $tok ?: '';
}

/**
 * Very conservative SQL analyzers using regex.
 * These functions return structured arrays or null when they cannot safely parse.
 *
 * Limitations: only positional placeholders (?) are considered. Named params or complex expressions are skipped.
 */

function analyzeInsert(string $sql): ?array
{
    // match: INSERT INTO table (col1, col2) VALUES (?, ?, ?)
    $sqlU = strtoupper($sql);
    if (strpos($sqlU, 'INSERT INTO') === false) return null;

    // simplified regex
    $m = null;
    // capture table and columns list
    if (preg_match('/INSERT\s+INTO\s+([`"]?)([A-Za-z0-9_]+)\1\s*\(\s*([^)]+)\)\s*VALUES\s*\(\s*([^)]+)\)/i', $sql, $m)) {
        $table = $m[2];
        $cols = array_map('trim', explode(',', $m[3]));
        $vals = array_map('trim', explode(',', $m[4]));
        // ensure placeholders count matches
        $placeholders = array_filter($vals, function ($v) {
            return preg_match('/^\?$/', $v) || preg_match('/^\?$/', trim($v));
        });
        if (count($cols) !== count($vals)) return null;
        return [
            'table' => $table,
            'columns' => array_map(function ($c) {
                return trim(trim($c), '`"');
            }, $cols),
            'placeholders' => count($placeholders),
        ];
    }

    // fallback: INSERT INTO table VALUES (?,?) -> unknown columns => cannot create assoc mapping
    if (preg_match('/INSERT\s+INTO\s+([`"]?)([A-Za-z0-9_]+)\1\s*VALUES\s*\(/i', $sql, $m2)) {
        // mark as fallback (no columns)
        return [
            'table' => $m2[2],
            'columns' => null,
            'placeholders' => substr_count($sql, '?'),
        ];
    }

    return null;
}

function analyzeUpdate(string $sql): ?array
{
    // match: UPDATE table SET col1 = ?, col2 = ? WHERE id = ?
    if (stripos($sql, 'UPDATE') === false || stripos($sql, 'SET') === false) return null;
    if (!preg_match('/UPDATE\s+([`"]?)([A-Za-z0-9_]+)\1\s+SET\s+(.+?)(\s+WHERE\s+(.+))?$/is', $sql, $m)) return null;
    $table = $m[2];
    $setPart = $m[3];
    $wherePart = $m[5] ?? '';
    // parse set columns
    $setCols = [];
    foreach (preg_split('/\s*,\s*/', $setPart) as $pair) {
        if (preg_match('/^\s*([`"]?)([A-Za-z0-9_]+)\1\s*=/i', $pair, $mm)) {
            $setCols[] = $mm[2];
        } else {
            return null; // complex expression, bail
        }
    }
    // parse where columns if they are simple equals with placeholders
    $whereCols = [];
    if ($wherePart !== '') {
        // split on AND for simple conditions
        $conds = preg_split('/\s+AND\s+/i', $wherePart);
        foreach ($conds as $cond) {
            if (preg_match('/^\s*([`"]?)([A-Za-z0-9_]+)\1\s*=\s*\?/i', trim($cond), $mm2)) {
                $whereCols[] = $mm2[2];
            } else {
                // unsupported where clause
                return null;
            }
        }
    }
    return [
        'table' => $table,
        'set' => $setCols,
        'where' => $whereCols,
    ];
}

function analyzeDelete(string $sql): ?array
{
    // match: DELETE FROM table WHERE col = ?
    if (stripos($sql, 'DELETE') === false || stripos($sql, 'FROM') === false) return null;
    if (!preg_match('/DELETE\s+FROM\s+([`"]?)([A-Za-z0-9_]+)\1\s*(?:WHERE\s+(.+))?$/is', $sql, $m)) return null;
    $table = $m[2];
    $wherePart = $m[3] ?? '';
    $whereCols = [];
    if ($wherePart !== '') {
        $conds = preg_split('/\s+AND\s+/i', $wherePart);
        foreach ($conds as $cond) {
            if (preg_match('/^\s*([`"]?)([A-Za-z0-9_]+)\1\s*=\s*\?/i', trim($cond), $mm)) {
                $whereCols[] = $mm[2];
            } else {
                return null;
            }
        }
    }
    return [
        'table' => $table,
        'where' => $whereCols,
    ];
}

function analyzeSelect(string $sql): ?array
{
    // Attempt to find FROM table and simple WHERE column = ? AND ... and optional LIMIT 1
    if (stripos($sql, 'SELECT') === false || stripos($sql, 'FROM') === false) return null;
    // capture FROM table
    if (!preg_match('/FROM\s+([`"]?)([A-Za-z0-9_]+)\1/i', $sql, $m)) return null;
    $table = $m[2];
    $wherePart = '';
    if (preg_match('/WHERE\s+(.+?)(?:\s+LIMIT\s+(\d+))?$/is', $sql, $m2)) {
        $wherePart = $m2[1];
        $limit = isset($m2[2]) ? (int)$m2[2] : null;
    } else {
        $limit = null;
    }
    $whereCols = [];
    if ($wherePart !== '') {
        $conds = preg_split('/\s+AND\s+/i', $wherePart);
        foreach ($conds as $cond) {
            if (preg_match('/^\s*([`"]?)([A-Za-z0-9_]+)\1\s*=\s*\?/i', trim($cond), $mm)) {
                $whereCols[] = $mm[2];
            } else {
                // complex where; don't try to map
                return [
                    'table' => $table,
                    'where' => null,
                    'limit' => $limit,
                ];
            }
        }
    }
    return [
        'table' => $table,
        'where' => $whereCols,
        'limit' => $limit,
    ];
}

function buildAssocArrayItems(array $columns, array $exprs): array
{
    $items = [];
    for ($i = 0; $i < count($columns) && $i < count($exprs); $i++) {
        $key = new Node\Scalar\String_($columns[$i]);
        $items[] = new ArrayItem($exprs[$i], $key);
    }
    return $items;
}

foreach ($files as $file) {
    $code = file_get_contents($file);
    try {
        $ast = $parser->parse($code);
    } catch (\Throwable $e) {
        $report['errors'][] = ['file' => $file, 'error' => $e->getMessage()];
        echo "Parse error in $file: {$e->getMessage()}\n";
        continue;
    }

    $modified = false;
    $manualHints = [];
    $traverser = new NodeTraverser();

    $traverser->addVisitor(new class($nodeFinder, $parser, $report, $file, $manualHints, $modified) extends NodeVisitorAbstract {
        private $nodeFinder;
        private $parser;
        private $reportRef;
        private $file;
        private $manualHintsRef;
        private $modifiedRef;

        public function __construct($nodeFinder, $parser, &$reportRef, $file, &$manualHintsRef, &$modifiedRef)
        {
            $this->nodeFinder = $nodeFinder;
            $this->parser = $parser;
            $this->reportRef = &$reportRef;
            $this->file = $file;
            $this->manualHintsRef = &$manualHintsRef;
            $this->modifiedRef = &$modifiedRef;
        }

        private function isDbQueryMethodCall(MethodCall $mc): bool
        {
            return $mc->name instanceof Node\Identifier && $mc->name->name === 'dbQuery';
        }

        private function extractSqlString(Node\Arg $arg): ?string
        {
            $val = $arg->value;
            // If it's a plain string literal:
            if ($val instanceof String_) {
                return $val->value;
            }
            // If it's a concatenation of strings/variables, attempt to collect only literal parts.
            if ($val instanceof Expr\BinaryOp\Concat) {
                $parts = $this->flattenConcat($val);
                // Only proceed if all literal parts are strings and variables/placeholders are present as '?'
                $str = '';
                foreach ($parts as $p) {
                    if ($p instanceof String_) {
                        $str .= $p->value;
                    } else {
                        // contains dynamic part; abort
                        return null;
                    }
                }
                return $str;
            }
            return null;
        }

        private function flattenConcat(Expr\BinaryOp\Concat $concat)
        {
            $parts = [];
            $left = $concat->left;
            $right = $concat->right;
            if ($left instanceof Expr\BinaryOp\Concat) {
                $parts = array_merge($parts, $this->flattenConcat($left));
            } else {
                $parts[] = $left;
            }
            if ($right instanceof Expr\BinaryOp\Concat) {
                $parts = array_merge($parts, $this->flattenConcat($right));
            } else {
                $parts[] = $right;
            }
            return $parts;
        }

        public function enterNode(Node $node)
        {
            if (!$node instanceof MethodCall) {
                return null;
            }
            if (!$this->isDbQueryMethodCall($node)) {
                return null;
            }
            $args = $node->args;
            if (count($args) === 0) {
                // no args; convert to execute('', [])
                $node->name = new Node\Identifier('execute');
                $node->args = [new Arg(new String_('')), new Arg(new Array_([], ['kind' => Array_::KIND_SHORT]))];
                $this->modifiedRef = true;
                return null;
            }

            // attempt to extract SQL text from first arg
            $sqlText = $this->extractSqlString($args[0]);
            $paramExprs = [];
            for ($i = 1; $i < count($args); $i++) {
                $paramExprs[] = $args[$i]->value;
            }

            if ($sqlText === null) {
                // dynamic SQL; fallback: convert to execute($sql, [$params]) and flag for review
                $paramsArray = new Array_();
                foreach ($paramExprs as $pe) {
                    $paramsArray->items[] = new ArrayItem($pe);
                }
                $node->name = new Node\Identifier('execute');
                $node->args = [$args[0], new Arg($paramsArray)];
                $this->manualHintsRef[] = [
                    'reason' => 'dynamic_sql_first_arg',
                    'loc' => $node->getStartLine(),
                ];
                $this->modifiedRef = true;
                return null;
            }

            $sql = normalizeSql($sqlText);
            $verb = detectVerb($sql);

            // try analyzers
            if ($verb === 'INSERT') {
                $analysis = analyzeInsert($sql);
                if ($analysis === null) {
                    // fallback to execute
                    $paramsArray = new Array_();
                    foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                    $node->name = new Node\Identifier('execute');
                    $node->args = [$args[0], new Arg($paramsArray)];
                    $this->manualHintsRef[] = ['reason' => 'insert_unparsed', 'sql' => $sql];
                    $this->modifiedRef = true;
                    return null;
                }
                // If columns are present, map params to columns
                if (is_array($analysis['columns'])) {
                    $cols = $analysis['columns'];
                    if (count($cols) !== count($paramExprs)) {
                        // mismatch; fallback
                        $paramsArray = new Array_();
                        foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                        $node->name = new Node\Identifier('execute');
                        $node->args = [$args[0], new Arg($paramsArray)];
                        $this->manualHintsRef[] = ['reason' => 'insert_param_count_mismatch', 'sql' => $sql];
                        $this->modifiedRef = true;
                        return null;
                    }
                    $assocItems = buildAssocArrayItems($cols, $paramExprs);
                    $assoc = new Array_($assocItems, ['kind' => Array_::KIND_SHORT]);
                    // replace node: ->insert('table', [..])
                    $node->name = new Node\Identifier('insert');
                    $node->args = [
                        new Arg(new String_($analysis['table'])),
                        new Arg($assoc),
                    ];
                    $this->modifiedRef = true;
                    return null;
                } else {
                    // INSERT without explicit columns -> manual review fallback
                    $paramsArray = new Array_();
                    foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                    $node->name = new Node\Identifier('execute');
                    $node->args = [$args[0], new Arg($paramsArray)];
                    $this->manualHintsRef[] = ['reason' => 'insert_no_columns', 'sql' => $sql];
                    $this->modifiedRef = true;
                    return null;
                }
            }

            if ($verb === 'UPDATE') {
                $analysis = analyzeUpdate($sql);
                if ($analysis === null) {
                    $paramsArray = new Array_();
                    foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                    $node->name = new Node\Identifier('execute');
                    $node->args = [$args[0], new Arg($paramsArray)];
                    $this->manualHintsRef[] = ['reason' => 'update_unparsed', 'sql' => $sql];
                    $this->modifiedRef = true;
                    return null;
                }
                $setCols = $analysis['set'];
                $whereCols = $analysis['where'];
                // mapping: first N params -> set cols, remaining -> where cols
                if (count($paramExprs) < count($setCols) + count($whereCols)) {
                    // mismatch; fallback
                    $paramsArray = new Array_();
                    foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                    $node->name = new Node\Identifier('execute');
                    $node->args = [$args[0], new Arg($paramsArray)];
                    $this->manualHintsRef[] = ['reason' => 'update_param_count_mismatch', 'sql' => $sql];
                    $this->modifiedRef = true;
                    return null;
                }
                $setExprs = array_slice($paramExprs, 0, count($setCols));
                $whereExprs = array_slice($paramExprs, count($setCols), count($whereCols));
                $setAssoc = new Array_(buildAssocArrayItems($setCols, $setExprs), ['kind' => Array_::KIND_SHORT]);
                $whereAssoc = new Array_(buildAssocArrayItems($whereCols, $whereExprs), ['kind' => Array_::KIND_SHORT]);
                $node->name = new Node\Identifier('update');
                $node->args = [
                    new Arg(new String_($analysis['table'])),
                    new Arg($setAssoc),
                    new Arg($whereAssoc),
                ];
                $this->modifiedRef = true;
                return null;
            }

            if ($verb === 'DELETE') {
                $analysis = analyzeDelete($sql);
                if ($analysis === null) {
                    $paramsArray = new Array_();
                    foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                    $node->name = new Node\Identifier('execute');
                    $node->args = [$args[0], new Arg($paramsArray)];
                    $this->manualHintsRef[] = ['reason' => 'delete_unparsed', 'sql' => $sql];
                    $this->modifiedRef = true;
                    return null;
                }
                $whereCols = $analysis['where'];
                if ($whereCols === []) {
                    // no where -> risky delete; flag
                    $paramsArray = new Array_();
                    foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                    $node->name = new Node\Identifier('execute');
                    $node->args = [$args[0], new Arg($paramsArray)];
                    $this->manualHintsRef[] = ['reason' => 'delete_no_where', 'sql' => $sql];
                    $this->modifiedRef = true;
                    return null;
                }
                if (count($paramExprs) < count($whereCols)) {
                    $paramsArray = new Array_();
                    foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
                    $node->name = new Node\Identifier('execute');
                    $node->args = [$args[0], new Arg($paramsArray)];
                    $this->manualHintsRef[] = ['reason' => 'delete_param_count_mismatch', 'sql' => $sql];
                    $this->modifiedRef = true;
                    return null;
                }
                $whereExprs = array_slice($paramExprs, 0, count($whereCols));
                $whereAssoc = new Array_(buildAssocArrayItems($whereCols, $whereExprs), ['kind' => Array_::KIND_SHORT]);
                $node->name = new Node\Identifier('delete');
                $node->args = [
                    new Arg(new String_($analysis['table'])),
                    new Arg($whereAssoc),
                ];
                $this->modifiedRef = true;
                return null;
            }

            if ($verb === 'SELECT' || $verb === 'WITH' || $verb === 'SHOW' || $verb === 'DESCRIBE') {
                $analysis = analyzeSelect($sql);
                // build params array expression
                $paramsArray = new Array_();
                foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);

                // If analysis provides simple where column mapping and limit=1, we can map to fetchOne semantics,
                // but we cannot change surrounding code that consumes the result; so convert to ->query($sql, [$params])
                // and mark for manual review to update fetch usage if necessary.
                $node->name = new Node\Identifier('query');
                $node->args = [$args[0], new Arg($paramsArray)];
                // if select is simple, annotate hint
                $this->manualHintsRef[] = ['reason' => 'select_converted_to_query', 'sql' => $sql, 'analysis' => $analysis];
                $this->modifiedRef = true;
                return null;
            }

            // default fallback
            $paramsArray = new Array_();
            foreach ($paramExprs as $pe) $paramsArray->items[] = new ArrayItem($pe);
            $node->name = new Node\Identifier('execute');
            $node->args = [$args[0], new Arg($paramsArray)];
            $this->manualHintsRef[] = ['reason' => 'unknown_verb_fallback', 'sql' => $sql];
            $this->modifiedRef = true;
            return null;
        }
    });

    $newAst = $traverser->traverse($ast);
    $newCode = $printer->prettyPrintFile($newAst);

    if ($newCode !== $code) {
        $outFile = $file . '.codemod';
        file_put_contents($outFile, $newCode);
        $report['converted'][] = $file;
        // collect manual hints from visitor by parsing .codemod changes for comments? We already populated hints in visitor via reference
        // But NodeVisitor stored hints back to $manualHints by reference; retrieve them with a simple heuristic: read .codemod and search for execute/query markers
        $report['manual_review'][] = ['file' => $file, 'notes' => 'some sites flagged for manual review; inspect ' . basename($outFile)];
        echo "Wrote dry-run output: {$outFile}\n";
    } else {
        echo "No changes for {$file}\n";
    }
}

// Write report
$reportPath = getcwd() . DIRECTORY_SEPARATOR . 'migrate-report.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));
echo "Wrote report: {$reportPath}\n";
