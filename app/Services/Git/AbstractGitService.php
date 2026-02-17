<?php

declare(strict_types=1);

namespace App\Services\Git;

abstract class AbstractGitService
{
    protected GitCacheService $cache;

    protected GitCommandRunner $commandRunner;

    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
        $this->cache = new GitCacheService;
        $this->commandRunner = new GitCommandRunner($this->repoPath);
    }
}
