<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Process;

class CommitService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function commit(string $message): void
    {
        Process::path($this->repoPath)->run("git commit -m \"{$message}\"");
    }

    public function commitAmend(string $message): void
    {
        Process::path($this->repoPath)->run("git commit --amend -m \"{$message}\"");
    }

    public function commitAndPush(string $message): void
    {
        Process::path($this->repoPath)->run("git commit -m \"{$message}\"");
        Process::path($this->repoPath)->run('git push');
    }

    public function lastCommitMessage(): string
    {
        $result = Process::path($this->repoPath)->run('git log -1 --pretty=%B');

        return trim($result->output());
    }
}
