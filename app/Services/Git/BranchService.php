<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Branch;
use App\DTOs\MergeResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class BranchService
{
    private GitCacheService $cache;

    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
        $this->cache = new GitCacheService;
    }

    public function branches(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'branches',
            function () {
                $result = Process::path($this->repoPath)->run('git branch -a -vv');
                $lines = array_filter(explode("\n", trim($result->output())));

                return collect($lines)->map(fn ($line) => Branch::fromBranchLine($line));
            },
            30
        );
    }

    public function switchBranch(string $name): void
    {
        $result = Process::path($this->repoPath)->run("git checkout {$name}");

        if ($result->exitCode() !== 0) {
            $errorMsg = trim($result->errorOutput() ?: $result->output());
            throw new \RuntimeException($errorMsg);
        }

        $this->cache->invalidateGroup($this->repoPath, 'branches');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function createBranch(string $name, string $from): void
    {
        $result = Process::path($this->repoPath)->run("git checkout -b {$name} {$from}");

        if ($result->exitCode() !== 0) {
            $errorMsg = trim($result->errorOutput() ?: $result->output());
            throw new \RuntimeException($errorMsg);
        }

        $this->cache->invalidateGroup($this->repoPath, 'branches');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function deleteBranch(string $name, bool $force): void
    {
        $flag = $force ? '-D' : '-d';
        $result = Process::path($this->repoPath)->run("git branch {$flag} {$name}");

        if ($result->exitCode() !== 0) {
            $errorMsg = trim($result->errorOutput() ?: $result->output());
            throw new \RuntimeException($errorMsg);
        }

        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function mergeBranch(string $name): MergeResult
    {
        $result = Process::path($this->repoPath)->run("git merge {$name}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');

        return MergeResult::fromMergeOutput($result->output(), $result->exitCode());
    }
}
