<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\MergeResult;
use Illuminate\Support\Facades\Process;

class CommitService
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

    public function commit(string $message): void
    {
        $this->runCommit("git commit -m \"{$message}\"", 'Git commit failed');
    }

    public function commitAmend(string $message): void
    {
        $this->runCommit("git commit --amend -m \"{$message}\"", 'Git commit amend failed');
    }

    public function commitAndPush(string $message): void
    {
        $this->runCommit("git commit -m \"{$message}\"", 'Git commit failed');

        $pushResult = Process::path($this->repoPath)->run('git push');

        if ($pushResult->exitCode() !== 0) {
            throw new \RuntimeException('Git push failed: '.$pushResult->errorOutput());
        }
    }

    private function runCommit(string $command, string $errorPrefix): void
    {
        $result = Process::path($this->repoPath)->run($command);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException($errorPrefix.': '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function lastCommitMessage(): string
    {
        $result = Process::path($this->repoPath)->run('git log -1 --pretty=%B');

        return trim($result->output());
    }

    public function undoLastCommit(): void
    {
        $process = Process::path($this->repoPath)->run('git reset --soft HEAD~1');

        if (! $process->successful()) {
            throw new \RuntimeException('Git reset failed: '.$process->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function isLastCommitPushed(): bool
    {
        $gitService = new GitService($this->repoPath);
        $status = $gitService->status();

        if (empty($status->upstream)) {
            return false;
        }

        $aheadBehind = $gitService->aheadBehind();

        return ($aheadBehind['ahead'] ?? 0) === 0;
    }

    public function isLastCommitMerge(): bool
    {
        $process = Process::path($this->repoPath)->run('git rev-parse HEAD^2 2>/dev/null');

        return $process->successful();
    }

    public function cherryPick(string $sha): MergeResult
    {
        $result = Process::path($this->repoPath)->run("git cherry-pick {$sha}");

        $mergeResult = MergeResult::fromMergeOutput($result->output().$result->errorOutput(), $result->exitCode());

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');

        return $mergeResult;
    }

    public function cherryPickAbort(): void
    {
        $result = Process::path($this->repoPath)->run('git cherry-pick --abort');

        if (! $result->successful()) {
            throw new \RuntimeException('Git cherry-pick abort failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function cherryPickContinue(): void
    {
        $result = Process::path($this->repoPath)->run('git cherry-pick --continue');

        if (! $result->successful()) {
            throw new \RuntimeException('Git cherry-pick continue failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }
}
