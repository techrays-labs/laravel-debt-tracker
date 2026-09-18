<?php

declare(strict_types=1);

use TechRaysLabs\DebtTracker\Analyzers\AstParser;

it('parses valid PHP and returns statement nodes', function () {
    $path = tempnam(sys_get_temp_dir(), 'ast_parser_');
    file_put_contents($path, "<?php\nclass Foo { public function bar(): int { return 1; } }\n");

    $ast = (new AstParser)->parse($path);

    unlink($path);

    expect($ast)->not->toBeNull()->and($ast)->not->toBeEmpty();
});

it('does not accumulate memory across many distinct files (no unbounded per-scan cache)', function () {
    // Regression test for a real crash found via QA-4 large-repo testing:
    // AstParser used to cache every parsed AST for the life of the parser
    // instance, but each file is parsed exactly once per scan (FileAnalyzer
    // passes the resulting $ast to every detector via $context — nothing
    // ever calls parse() twice on the same path), so the cache had a 0%
    // hit rate and just accumulated memory across an entire scan. On a
    // real ~1,700-file project this exhausted PHP's default 128MB
    // memory_limit and fatally crashed debt:scan / debt:mcp-serve.
    $parser = new AstParser;

    // A non-trivial method body per file so a retained AST is not
    // negligible — 500 files is a modest stand-in for a "large repo".
    $body = implode("\n", array_map(static fn ($i) => "        \$v{$i} = {$i};", range(1, 30)));

    gc_collect_cycles();
    $before = memory_get_usage();

    for ($i = 0; $i < 500; $i++) {
        $path = tempnam(sys_get_temp_dir(), 'ast_parser_');
        file_put_contents($path, "<?php\nclass Generated{$i} {\n    public function run(): void {\n{$body}\n    }\n}\n");

        $parser->parse($path);

        unlink($path);
    }

    gc_collect_cycles();
    $growth = memory_get_usage() - $before;

    // With the cache bug, 500 retained ASTs of this size grow usage by
    // several megabytes; without it, growth is negligible (no file's AST
    // outlives its own parse() call). 2MB is comfortably below the buggy
    // behavior and comfortably above normal fluctuation.
    expect($growth)->toBeLessThan(2 * 1024 * 1024);
});
