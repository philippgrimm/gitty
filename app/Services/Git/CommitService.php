<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\MergeResult;

class CommitService extends AbstractGitService
{
    public function commit(string $message): void
    {
        $this->commandRunner->runOrFail('commit -m', [$message], 'Git commit failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function commitAmend(string $message): void
    {
        $this->commandRunner->runOrFail('commit --amend -m', [$message], 'Git commit amend failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function commitAndPush(string $message): void
    {
        $this->commandRunner->runOrFail('commit -m', [$message], 'Git commit failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');

        $this->commandRunner->runOrFail('push', [], 'Git push failed');
    }

    public function lastCommitMessage(): string
    {
        $result = $this->commandRunner->run('log -1 --pretty=%B');

        return trim($result->output());
    }

    public function undoLastCommit(): void
    {
        $this->commandRunner->runOrFail('reset --soft HEAD~1', [], 'Git reset failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function isLastCommitPushed(): bool
    {
        $result = $this->commandRunner->run('status --porcelain=v2 --branch');
        $output = $result->output();

        // Check if there's an upstream
        if (! str_contains($output, '# branch.upstream')) {
            return false;
        }

        // Check ahead count
        if (preg_match('/# branch\.ab \+(\d+) -(\d+)/', $output, $matches)) {
            return (int) $matches[1] === 0;
        }

        return true;
    }

    public function isLastCommitMerge(): bool
    {
        $result = $this->commandRunner->run('rev-parse HEAD^2 2>/dev/null');

        return $result->successful();
    }

    public function cherryPick(string $sha): MergeResult
    {
        $result = $this->commandRunner->run('cherry-pick', [$sha]);

        $mergeResult = MergeResult::fromMergeOutput($result->output().$result->errorOutput(), $result->exitCode());

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');

        return $mergeResult;
    }

    public function cherryPickAbort(): void
    {
        $this->commandRunner->runOrFail('cherry-pick --abort', [], 'Git cherry-pick abort failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function cherryPickContinue(): void
    {
        $this->commandRunner->runOrFail('cherry-pick --continue', [], 'Git cherry-pick continue failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }
}
