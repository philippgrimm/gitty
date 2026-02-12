<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Process;

class StagingService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function stageFile(string $file): void
    {
        Process::path($this->repoPath)->run("git add {$file}");
    }

    public function unstageFile(string $file): void
    {
        Process::path($this->repoPath)->run("git reset HEAD {$file}");
    }

    public function stageAll(): void
    {
        Process::path($this->repoPath)->run('git add .');
    }

    public function unstageAll(): void
    {
        Process::path($this->repoPath)->run('git reset HEAD');
    }

    public function discardFile(string $file): void
    {
        Process::path($this->repoPath)->run("git checkout -- {$file}");
    }

    public function discardAll(): void
    {
        Process::path($this->repoPath)->run('git checkout -- .');
    }
}
