<?php

declare(strict_types=1);

use App\Helpers\FileTreeBuilder;

test('builds tree from nested file paths', function () {
    $flatFiles = [
        ['path' => 'src/app/Model.php', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
        ['path' => 'src/routes.php', 'indexStatus' => 'A', 'worktreeStatus' => '.', 'oldPath' => null],
        ['path' => 'README.md', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
    ];

    $tree = FileTreeBuilder::buildTree($flatFiles);

    expect($tree)->toHaveCount(2);
    expect($tree[0]['type'])->toBe('directory');
    expect($tree[0]['name'])->toBe('src');
    expect($tree[0]['children'])->toHaveCount(2);
    expect($tree[1]['type'])->toBe('file');
    expect($tree[1]['name'])->toBe('README.md');
});

test('sorts directories before files alphabetically', function () {
    $flatFiles = [
        ['path' => 'zebra.txt', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
        ['path' => 'app/Model.php', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
        ['path' => 'apple.txt', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
        ['path' => 'zoo/Animal.php', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
    ];

    $tree = FileTreeBuilder::buildTree($flatFiles);

    expect($tree)->toHaveCount(4);
    expect($tree[0]['type'])->toBe('directory');
    expect($tree[0]['name'])->toBe('app');
    expect($tree[1]['type'])->toBe('directory');
    expect($tree[1]['name'])->toBe('zoo');
    expect($tree[2]['type'])->toBe('file');
    expect($tree[2]['name'])->toBe('apple.txt');
    expect($tree[3]['type'])->toBe('file');
    expect($tree[3]['name'])->toBe('zebra.txt');
});

test('handles single-level files without directories', function () {
    $flatFiles = [
        ['path' => 'README.md', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
        ['path' => 'LICENSE', 'indexStatus' => 'A', 'worktreeStatus' => '.', 'oldPath' => null],
    ];

    $tree = FileTreeBuilder::buildTree($flatFiles);

    expect($tree)->toHaveCount(2);
    expect($tree[0]['type'])->toBe('file');
    expect($tree[0]['name'])->toBe('LICENSE');
    expect($tree[1]['type'])->toBe('file');
    expect($tree[1]['name'])->toBe('README.md');
});

test('preserves file metadata in tree nodes', function () {
    $flatFiles = [
        ['path' => 'src/Model.php', 'indexStatus' => 'M', 'worktreeStatus' => 'M', 'oldPath' => null],
    ];

    $tree = FileTreeBuilder::buildTree($flatFiles);

    $file = $tree[0]['children'][0];
    expect($file['type'])->toBe('file');
    expect($file['path'])->toBe('src/Model.php');
    expect($file['indexStatus'])->toBe('M');
    expect($file['worktreeStatus'])->toBe('M');
    expect($file['oldPath'])->toBeNull();
});

test('handles deeply nested directory structures', function () {
    $flatFiles = [
        ['path' => 'a/b/c/d/e/file.txt', 'indexStatus' => 'M', 'worktreeStatus' => '.', 'oldPath' => null],
    ];

    $tree = FileTreeBuilder::buildTree($flatFiles);

    expect($tree)->toHaveCount(1);
    expect($tree[0]['name'])->toBe('a');
    expect($tree[0]['children'][0]['name'])->toBe('b');
    expect($tree[0]['children'][0]['children'][0]['name'])->toBe('c');
    expect($tree[0]['children'][0]['children'][0]['children'][0]['name'])->toBe('d');
    expect($tree[0]['children'][0]['children'][0]['children'][0]['children'][0]['name'])->toBe('e');
    expect($tree[0]['children'][0]['children'][0]['children'][0]['children'][0]['children'][0]['name'])->toBe('file.txt');
});
