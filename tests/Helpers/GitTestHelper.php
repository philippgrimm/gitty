<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

class GitTestHelper
{
    public static function createTestRepo(string $path): void
    {
        if (File::exists($path)) {
            self::cleanupTestRepo($path);
        }

        File::makeDirectory($path, 0755, true);

        Process::path($path)->run('git init');
        Process::path($path)->run('git config user.email "test@example.com"');
        Process::path($path)->run('git config user.name "Test User"');

        File::put("{$path}/README.md", "# Test Repository\n");
        Process::path($path)->run('git add .');
        Process::path($path)->run('git commit -m "Initial commit"');
    }

    public static function addTestFiles(string $repoPath, array $files): void
    {
        foreach ($files as $filename => $content) {
            $fullPath = "{$repoPath}/{$filename}";
            $directory = dirname($fullPath);

            if (! File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            File::put($fullPath, $content);
        }

        Process::path($repoPath)->run('git add .');
    }

    public static function modifyTestFiles(string $repoPath, array $files): void
    {
        foreach ($files as $filename => $content) {
            $fullPath = "{$repoPath}/{$filename}";

            if (! File::exists($fullPath)) {
                throw new \RuntimeException("File {$filename} does not exist in {$repoPath}");
            }

            File::put($fullPath, $content);
        }
    }

    public static function createConflict(string $repoPath): void
    {
        $currentBranch = trim(Process::path($repoPath)->run('git rev-parse --abbrev-ref HEAD')->output());

        Process::path($repoPath)->run('git checkout -b conflict-branch');

        File::put("{$repoPath}/conflict.txt", "Content from conflict branch\n");
        Process::path($repoPath)->run('git add conflict.txt');
        Process::path($repoPath)->run('git commit -m "Add conflict file from branch"');

        Process::path($repoPath)->run("git checkout {$currentBranch}");

        File::put("{$repoPath}/conflict.txt", "Content from main branch\n");
        Process::path($repoPath)->run('git add conflict.txt');
        Process::path($repoPath)->run('git commit -m "Add conflict file from main"');

        Process::path($repoPath)->run('git merge conflict-branch');
    }

    public static function createDetachedHead(string $repoPath): void
    {
        $result = Process::path($repoPath)->run('git rev-parse HEAD');
        $commitSha = trim($result->output());

        Process::path($repoPath)->run("git checkout {$commitSha}");
    }

    public static function cleanupTestRepo(string $path): void
    {
        if (File::exists($path)) {
            File::deleteDirectory($path);
        }
    }
}
