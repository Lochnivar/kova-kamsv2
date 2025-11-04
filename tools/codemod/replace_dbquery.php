<?php
declare(strict_types=1);

require __DIR__ . '/../../vendor/autoload.php';

use PhpParser\{ParserFactory, NodeTraverser, NodeVisitorAbstract, PrettyPrinter};
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\ArrayItem;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Expression;
use PhpParser\Node\Expr\BinaryOp\Concat;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;

// Usage: php replace_dbquery.php file1.php file2.php ...
$files = array_slice($argv, 1);
if (count($files) === 0) {
    echo "Usage: php replace_dbquery.php path/to/file.php [more files...]\n";
    exit(1);
}

$parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);
$printer = new PrettyPrinter\Standard();

foreach ($files as $file) {
    $code = file_get_contents($file);
    try {
        $ast = $parser->parse($code);
    } catch (\Throwable $e) {
        echo "Parse error in $file: {$e->getMessage()}\n";
        continue;
    }

    $modified = false;
    $traverser = new NodeTraverser();

    $traverser->addVisitor(new class(&$modified) extends NodeVisitorAbstract {
        private $modifiedRef;
        public function __construct(&$modifiedRef) { $this->modifiedRef = &$modifiedRef; }

        private function isDbQueryCall(MethodCall $mc): bool
        {
            return $mc->name instanceof Node\Identifier && $mc->name->name === 'dbQuery';
        }

        private function firstArgIsSQL(Node\Arg $arg): ?string
        {
            // If it's a simple string literal, return uppercased trimmed verb
            $val = $arg->value;
            if ($val instanceof String_) {
                $s = trim($val->value);
                $tok = strtoupper(strtok($s, " \t\n\r\0\x0B"));
                return $tok ?: null;
            }
            return null;
        }

        public function enterNode(Node $node) {
            // transform plain method calls $x->dbQuery($sql, ...$params)
            if ($node instanceof MethodCall && $this->isDbQueryCall($node)) {
                $args = $node->args;
                if (count($args) === 0) {
                    // no args; convert to execute('', []) to keep semantics safe
                    $node->name = new Node\Identifier('execute');
                    $node->args = [ new Arg(new String_('')), new Arg(new Array_([], ['kind' => Array_::KIND_SHORT])) ];
                    $this->modifiedRef = true;
                    return null;
                }

                // determine SQL verb if first arg is a string literal
                $verb = null;
                if ($args[0] instanceof Arg) {
                    $verb = $this->firstArgIsSQL($args[0]);
                }

                // build array of remaining params
                $items = [];
                for ($i = 1; $i < count($args); $i++) {
                    $items[] = new ArrayItem($args[$i]->value);
                }
                $paramsArray = new Array_($items, ['kind' => Array_::KIND_SHORT]);

                // new args: first arg (SQL), second arg: params array
                $newArgs = [ $args[0], new Arg($paramsArray) ];
                // choose method name
                $newMethod = ($verb === 'SELECT' || $verb === 'SHOW' || $verb === 'WITH' || $verb === 'DESCRIBE') ? 'query' : 'execute';

                $node->name = new Node\Identifier($newMethod);
                $node->args = $newArgs;
                $this->modifiedRef = true;
                return null;
            }

            // transform chained uses like $this->dbConn->dbQuery($sql, ...)->fetch_row() or ->fetch_assoc()
            // We approximate: if node is a MethodCall and its var is another MethodCall that was dbQuery, leave above transform to run,
            // and allow later code to call ->fetch... on the returned Result. But for common legacy patterns that call ->dbQuery(...)->fetchAll(),
            // best to review manually. We do not attempt to inline fetch changes here to avoid incorrect assumptions.

            return null;
        }
    });

    $newAst = $traverser->traverse($ast);
    $newCode = $printer->prettyPrintFile($newAst);

    if ($newCode !== $code) {
        $outFile = $file . '.codemod';
        file_put_contents($outFile, $newCode);
        echo "Wrote dry-run output: {$outFile}\n";
        $modified = true;
    } else {
        echo "No changes for {$file}\n";
    }
}
