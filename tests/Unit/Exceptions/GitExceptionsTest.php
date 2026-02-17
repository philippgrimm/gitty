<?php

use App\Exceptions\GitCommandFailedException;
use App\Exceptions\GitConflictException;
use App\Exceptions\InvalidRepositoryException;

test('GitCommandFailedException extends RuntimeException', function () {
    $exception = new GitCommandFailedException('git push', 'remote not found', 1);

    expect($exception)
        ->toBeInstanceOf(\RuntimeException::class)
        ->getMessage()->toBe('git push: remote not found')
        ->and($exception->getCode())->toBe(1);
});

test('GitCommandFailedException works without error output', function () {
    $exception = new GitCommandFailedException('git status');

    expect($exception->getMessage())->toBe('Git command failed: git status');
});

test('InvalidRepositoryException extends InvalidArgumentException', function () {
    $exception = new InvalidRepositoryException('/fake/path');

    expect($exception)
        ->toBeInstanceOf(\InvalidArgumentException::class)
        ->getMessage()->toBe('Not a valid git repository: /fake/path');
});

test('GitConflictException extends RuntimeException', function () {
    $exception = new GitConflictException('Rebase');

    expect($exception)
        ->toBeInstanceOf(\RuntimeException::class)
        ->getMessage()->toBe('Rebase failed due to conflicts. Resolve conflicts and continue.');
});

test('GitConflictException defaults to rebase operation', function () {
    $exception = new GitConflictException;

    expect($exception->getMessage())->toBe('rebase failed due to conflicts. Resolve conflicts and continue.');
});
