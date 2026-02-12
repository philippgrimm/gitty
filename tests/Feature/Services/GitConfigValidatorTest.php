<?php

declare(strict_types=1);

use App\Services\Git\GitConfigValidator;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $testDir = '/tmp/gitty-test-repo';
    if (! is_dir($testDir . '/.git')) {
        mkdir($testDir . '/.git', 0755, true);
    }
});

test('it validates repository path has .git directory', function () {
    expect(fn () => new GitConfigValidator('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it detects missing user.name configuration', function () {
    Process::fake([
        'git config user.name' => '',
        'git config user.email' => 'test@example.com',
        'git --version' => 'git version 2.35.0',
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validate();

    expect($issues)->toContain('Git user.name is not configured');
});

test('it detects missing user.email configuration', function () {
    Process::fake([
        'git config user.name' => 'Test User',
        'git config user.email' => '',
        'git --version' => 'git version 2.35.0',
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validate();

    expect($issues)->toContain('Git user.email is not configured');
});

test('it detects git version below 2.30.0', function () {
    Process::fake([
        'git config user.name' => 'Test User',
        'git config user.email' => 'test@example.com',
        'git --version' => 'git version 2.25.0',
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validate();

    expect($issues)->toContain('Git version 2.25.0 is too old (minimum 2.30.0 required)');
});

test('it passes validation with git version 2.30.0', function () {
    Process::fake([
        'git config user.name' => 'Test User',
        'git config user.email' => 'test@example.com',
        'git --version' => 'git version 2.30.0',
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validate();

    expect($issues)->toBeEmpty();
});

test('it passes validation with git version above 2.30.0', function () {
    Process::fake([
        'git config user.name' => 'Test User',
        'git config user.email' => 'test@example.com',
        'git --version' => 'git version 2.45.1',
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validate();

    expect($issues)->toBeEmpty();
});

test('checkGitBinary returns true when git is found', function () {
    Process::fake([
        'which git' => '/usr/bin/git',
    ]);

    $result = GitConfigValidator::checkGitBinary();

    expect($result)->toBeTrue();
});

test('checkGitBinary returns false when git is not found', function () {
    Process::fake([
        'which git' => Process::result('', exitCode: 1),
    ]);

    $result = GitConfigValidator::checkGitBinary();

    expect($result)->toBeFalse();
});

test('validateAll returns all configuration issues', function () {
    Process::fake([
        'which git' => '/usr/bin/git',
        'git config user.name' => '',
        'git config user.email' => '',
        'git --version' => 'git version 2.25.0',
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validateAll();

    expect($issues)->toHaveCount(3)
        ->and($issues)->toContain('Git user.name is not configured')
        ->and($issues)->toContain('Git user.email is not configured')
        ->and($issues)->toContain('Git version 2.25.0 is too old (minimum 2.30.0 required)');
});

test('validateAll includes git binary check', function () {
    Process::fake([
        'which git' => Process::result('', exitCode: 1),
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validateAll();

    expect($issues)->toContain('Git is not installed or not in PATH');
});

test('validateAll returns empty array when all checks pass', function () {
    Process::fake([
        'which git' => '/usr/bin/git',
        'git config user.name' => 'Test User',
        'git config user.email' => 'test@example.com',
        'git --version' => 'git version 2.35.0',
    ]);

    $validator = new GitConfigValidator('/tmp/gitty-test-repo');
    $issues = $validator->validateAll();

    expect($issues)->toBeEmpty();
});
