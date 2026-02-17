<?php

use App\Services\Git\GitCommandRunner;
use Illuminate\Support\Facades\Process;

test('GitCommandRunner escapes double quotes in arguments', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('commit -m', ['Hello "world"']);

    Process::assertRan(fn ($process) => str_contains($process->command, 'escapeshellarg')
        || str_contains($process->command, "'Hello \"world\"'")
        || ! str_contains($process->command, '"world"'));
});

test('GitCommandRunner escapes semicolons in arguments', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('commit -m', ['fix: something; echo pwned']);

    Process::assertRan(fn ($process) => ! str_contains($process->command, '; echo pwned')
        || str_contains($process->command, "'fix: something; echo pwned'"));
});

test('GitCommandRunner escapes backticks in arguments', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('checkout', ['`rm -rf /`']);

    Process::assertRan(fn ($process) => str_contains($process->command, "'`rm -rf /`'"));
});

test('GitCommandRunner escapes dollar signs in arguments', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('log --grep', ['$(whoami)']);

    Process::assertRan(fn ($process) => str_contains($process->command, "'$(whoami)'"));
});

test('GitCommandRunner escapes single quotes in arguments', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('commit -m', ["it's a test"]);

    Process::assertRan(fn ($process) => str_contains($process->command, 'git commit -m')
        && ! str_contains($process->command, "it's a test")
        || str_contains($process->command, "'it'\\''s a test'"));
});

test('GitCommandRunner handles empty arguments array', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('status');

    Process::assertRan('git status');
});

test('GitCommandRunner escapes file paths with spaces', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('add', ['path/to/my file.txt']);

    Process::assertRan(fn ($process) => str_contains($process->command, "'path/to/my file.txt'"));
});

test('GitCommandRunner escapes multiple arguments independently', function () {
    Process::fake([
        '*' => Process::result(''),
    ]);

    $runner = new GitCommandRunner('/tmp/fake-repo');
    $runner->run('push', ['origin', 'main']);

    Process::assertRan("git push 'origin' 'main'");
});
