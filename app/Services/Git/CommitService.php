<?php

declare(strict_types=1);

namespace App\Services\Git;

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
}
