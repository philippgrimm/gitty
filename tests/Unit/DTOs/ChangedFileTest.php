<?php

declare(strict_types=1);

use App\DTOs\ChangedFile;

test('ChangedFile can be constructed with all properties', function () {
    $file = new ChangedFile(
        path: 'src/App.php',
        oldPath: null,
        indexStatus: 'M',
        worktreeStatus: '.',
    );

    expect($file->path)->toBe('src/App.php');
    expect($file->oldPath)->toBeNull();
    expect($file->indexStatus)->toBe('M');
    expect($file->worktreeStatus)->toBe('.');
});

test('ChangedFile isStaged returns true for staged files', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'M', worktreeStatus: '.');
    expect($file->isStaged())->toBeTrue();

    $file2 = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'A', worktreeStatus: '.');
    expect($file2->isStaged())->toBeTrue();

    $file3 = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'D', worktreeStatus: '.');
    expect($file3->isStaged())->toBeTrue();
});

test('ChangedFile isStaged returns false for unstaged-only files', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: '.', worktreeStatus: 'M');
    expect($file->isStaged())->toBeFalse();
});

test('ChangedFile isUnstaged returns true for worktree changes', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: '.', worktreeStatus: 'M');
    expect($file->isUnstaged())->toBeTrue();

    $file2 = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: '.', worktreeStatus: 'D');
    expect($file2->isUnstaged())->toBeTrue();
});

test('ChangedFile isUnstaged returns false for staged-only files', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'M', worktreeStatus: '.');
    expect($file->isUnstaged())->toBeFalse();
});

test('ChangedFile isUntracked returns true for untracked files', function () {
    $file = new ChangedFile(path: 'new.txt', oldPath: null, indexStatus: '?', worktreeStatus: '?');
    expect($file->isUntracked())->toBeTrue();
});

test('ChangedFile isUntracked returns false for tracked files', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'M', worktreeStatus: '.');
    expect($file->isUntracked())->toBeFalse();
});

test('ChangedFile isUnmerged returns true for conflicted files', function () {
    $file = new ChangedFile(path: 'conflict.txt', oldPath: null, indexStatus: 'U', worktreeStatus: 'U');
    expect($file->isUnmerged())->toBeTrue();
});

test('ChangedFile isUnmerged returns false for normal files', function () {
    $file = new ChangedFile(path: 'file.php', oldPath: null, indexStatus: 'M', worktreeStatus: '.');
    expect($file->isUnmerged())->toBeFalse();
});

test('ChangedFile statusLabel returns correct labels', function () {
    expect((new ChangedFile('f', null, 'M', '.'))->statusLabel())->toBe('modified');
    expect((new ChangedFile('f', null, 'A', '.'))->statusLabel())->toBe('added');
    expect((new ChangedFile('f', null, 'D', '.'))->statusLabel())->toBe('deleted');
    expect((new ChangedFile('f', null, 'R', '.'))->statusLabel())->toBe('renamed');
    expect((new ChangedFile('f', null, '?', '?'))->statusLabel())->toBe('untracked');
    expect((new ChangedFile('f', null, 'U', 'U'))->statusLabel())->toBe('unmerged');
    expect((new ChangedFile('f', null, '.', 'M'))->statusLabel())->toBe('modified');
    expect((new ChangedFile('f', null, '.', 'D'))->statusLabel())->toBe('deleted');
});

test('ChangedFile with renamed file has oldPath', function () {
    $file = new ChangedFile(
        path: 'new-name.txt',
        oldPath: 'old-name.txt',
        indexStatus: 'R',
        worktreeStatus: '.',
    );

    expect($file->oldPath)->toBe('old-name.txt');
    expect($file->statusLabel())->toBe('renamed');
});
