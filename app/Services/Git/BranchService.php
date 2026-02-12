<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Branch;
use App\DTOs\MergeResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class BranchService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function branches(): Collection
    {
        $result = Process::path($this->repoPath)->run('git branch -a -vv');
        $lines = array_filter(explode("\n", trim($result->output())));

        return collect($lines)->map(fn ($line) => Branch::fromBranchLine($line));
    }

    public function switchBranch(string $name): void
    {
        Process::path($this->repoPath)->run("git checkout {$name}");
    }

    public function createBranch(string $name, string $from): void
    {
        Process::path($this->repoPath)->run("git checkout -b {$name} {$from}");
    }

    public function deleteBranch(string $name, bool $force): void
    {
        $flag = $force ? '-D' : '-d';
        Process::path($this->repoPath)->run("git branch {$flag} {$name}");
    }

    public function mergeBranch(string $name): MergeResult
    {
        $result = Process::path($this->repoPath)->run("git merge {$name}");

        return MergeResult::fromMergeOutput($result->output(), $result->exitCode());
    }
}
