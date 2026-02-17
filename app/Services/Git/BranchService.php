<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Branch;
use App\DTOs\MergeResult;
use Illuminate\Support\Collection;

class BranchService extends AbstractGitService
{
    public function branches(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'branches',
            function () {
                $result = $this->commandRunner->run('branch -a -vv');
                $lines = array_filter(explode("\n", trim($result->output())));

                return collect($lines)->map(fn ($line) => Branch::fromBranchLine($line));
            },
            30
        );
    }

    public function switchBranch(string $name): void
    {
        $result = $this->commandRunner->run('checkout', [$name]);

        if ($result->exitCode() !== 0) {
            $errorMsg = trim($result->errorOutput() ?: $result->output());
            throw new \RuntimeException($errorMsg);
        }

        $this->cache->invalidateGroup($this->repoPath, 'branches');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function createBranch(string $name, string $from): void
    {
        $result = $this->commandRunner->run('checkout -b', [$name, $from]);

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
        $result = $this->commandRunner->run("branch {$flag}", [$name]);

        if ($result->exitCode() !== 0) {
            $errorMsg = trim($result->errorOutput() ?: $result->output());
            throw new \RuntimeException($errorMsg);
        }

        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function mergeBranch(string $name): MergeResult
    {
        $result = $this->commandRunner->run('merge', [$name]);

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');

        return MergeResult::fromMergeOutput($result->output(), $result->exitCode());
    }
}
