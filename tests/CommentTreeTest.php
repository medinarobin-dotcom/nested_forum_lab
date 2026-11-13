<?php
// Unit test for the tree builder. No database or Composer packages needed.
// Run:  php tests/CommentTreeTest.php

declare(strict_types=1);

require __DIR__ . '/../app/Support/CommentTree.php';

use App\Support\CommentTree;

$failures = 0;
function check(bool $condition, string $label): void
{
    global $failures;
    echo ($condition ? "PASS  " : "FAIL  ") . $label . PHP_EOL;
    if (!$condition) $failures++;
}

function row(int $id, ?int $parent, string $author): array
{
    return ['id' => $id, 'parent_id' => $parent, 'author' => $author, 'body' => "Comment $id", 'created_at' => '2026-11-01 09:00:00'];
}

// Same data as the lab handout (table 4.1).
$rows = [
    row(1, null, 'Alice'), row(2, 1, 'Ben'), row(3, 2, 'Cara'), row(4, 1, 'Dee'),
    row(5, null, 'Eli'), row(6, 3, 'Ben'), row(7, 6, 'Eli'),
];
$tree = CommentTree::build($rows);

check(count($tree) === 2, 'two top-level comments (Alice, Eli)');
check($tree[0]['author'] === 'Alice' && $tree[0]['depth'] === 0, 'Alice is depth 0');
check($tree[1]['author'] === 'Eli' && $tree[1]['depth'] === 0, 'Eli is depth 0');
check(count($tree[0]['children']) === 2, 'Alice has two replies (Ben, Dee are siblings)');
$eliDeep = $tree[0]['children'][0]['children'][0]['children'][0]['children'][0];
check($eliDeep['id'] === 7 && $eliDeep['depth'] === 4, 'comment 7 has displayed depth 4');
check($eliDeep['has_children'] === false && $tree[1]['has_children'] === false, 'leaves have no children');
check(str_ends_with($tree[0]['created_at'], 'UTC'), 'timestamps are labelled UTC');

// Indentation cap: build a chain 12 levels deep.
$chain = [row(1, null, 'A')];
for ($i = 2; $i <= 12; $i++) $chain[] = row($i, $i - 1, 'A');
$node = CommentTree::build($chain, 8)[0];
$lastIndented = null;
while ($node['children']) {
    if ($node['indent_children']) $lastIndented = $node['depth'] + 1;
    $node = $node['children'][0];
}
check($node['depth'] === 11, 'depth label stays accurate past the cap (11)');
check($lastIndented === 8, 'indentation stops at depth 8');

// Cycle (2 -> 3 -> 2) cannot be reached from a root and must not loop forever.
$cyclic = [row(1, null, 'A'), row(2, 3, 'B'), row(3, 2, 'C')];
$t = CommentTree::build($cyclic);
check(count($t) === 1 && $t[0]['children'] === [], 'disconnected cycle is skipped without looping');

// Traversal limit.
$many = [];
for ($i = 1; $i <= 50; $i++) $many[] = row($i, null, 'A');
check(count(CommentTree::build($many, 8, 10)) === 10, 'traversal limit stops at max nodes');

echo PHP_EOL . ($failures === 0 ? "All tests passed." : "$failures test(s) failed.") . PHP_EOL;
exit($failures === 0 ? 0 : 1);
