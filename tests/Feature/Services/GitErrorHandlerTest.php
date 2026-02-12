<?php

declare(strict_types=1);

use App\Services\Git\GitErrorHandler;

test('it translates not a git repository error', function () {
    $error = 'fatal: not a git repository (or any of the parent directories): .git';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('This folder is not a git repository');
});

test('it translates pathspec did not match error', function () {
    $error = "error: pathspec 'nonexistent.txt' did not match any file(s) known to git";
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('File not found in repository');
});

test('it translates merge conflict error', function () {
    $error = 'CONFLICT (content): Merge conflict in src/file.php';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('Merge conflict detected. Resolve conflicts in external editor.');
});

test('it translates push rejected error', function () {
    $error = 'error: failed to push some refs to origin
To https://github.com/user/repo.git
 ! [rejected]        main -> main (fetch first)';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('Push rejected. Pull remote changes first.');
});

test('it translates authentication failed error', function () {
    $error = 'fatal: Authentication failed for https://github.com/user/repo.git';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('Authentication failed. Check your credentials.');
});

test('it translates could not read username error', function () {
    $error = 'fatal: could not read Username for https://github.com: terminal prompts disabled';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('Authentication failed. Check your credentials.');
});

test('it translates git command not found error', function () {
    $error = 'sh: git: command not found';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('Git is not installed. Please install git.');
});

test('it translates git no such file error', function () {
    $error = '/bin/sh: git: No such file or directory';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('Git is not installed. Please install git.');
});

test('it translates bad object error', function () {
    $error = 'fatal: bad object HEAD';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe("Repository may be corrupted. Try running 'git fsck'.");
});

test('it translates loose object error', function () {
    $error = 'error: inflate: data stream error (incorrect header check)
fatal: loose object a1b2c3d4e5f6 (stored in .git/objects/a1/b2c3d4e5f6) is corrupt';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe("Repository may be corrupted. Try running 'git fsck'.");
});

test('it returns original error for unknown patterns', function () {
    $error = 'Some unknown git error message';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('Some unknown git error message');
});

test('it handles empty error strings', function () {
    $error = '';
    $translated = GitErrorHandler::translate($error);
    
    expect($translated)->toBe('');
});
