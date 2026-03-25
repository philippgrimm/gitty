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

    public function isCommitOnRemote(string $sha): bool
    {
        $result = $this->commandRunner->run('branch -r --contains', [$sha]);

        return $result->successful() && ! empty(trim($result->output()));
    }

    /**
     * Get last checkout timestamps per branch from git reflog.
     *
     * Parses reflog for "checkout: moving from X to Y" entries
     * and returns the most recent checkout time for each branch.
     *
     * @return array<string, int> Branch name => unix timestamp
     */
    public function getLastCheckoutTimestamps(): array
    {
        return $this->cache->get(
            $this->repoPath,
            'branch_checkout_timestamps',
            function () {
                $result = $this->commandRunner->run('reflog --date=unix -n 2000');

                if (! $result->successful() || empty(trim($result->output()))) {
                    return [];
                }

                $lines = array_filter(explode("\n", trim($result->output())));
                $timestamps = [];

                foreach ($lines as $line) {
                    if (preg_match('/HEAD@\{(\d+)\}.*checkout: moving from .+ to (.+)$/', $line, $matches)) {
                        $branch = trim($matches[2]);

                        // Only keep the first (most recent) occurrence per branch
                        if (! isset($timestamps[$branch])) {
                            $timestamps[$branch] = (int) $matches[1];
                        }
                    }
                }

                return $timestamps;
            },
            30
        );
    }

    public function mergeBranch(string $name): MergeResult
    {
        $result = $this->commandRunner->run('merge', [$name]);

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');

        return MergeResult::fromMergeOutput($result->output(), $result->exitCode());
    }
}
