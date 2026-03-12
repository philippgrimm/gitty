<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\Exceptions\InvalidRepositoryException;

abstract class AbstractGitService
{
    protected GitCacheService $cache;

    protected GitCommandRunner $commandRunner;

    public function __construct(
        protected string $repoPath,
        ?GitCacheService $cache = null,
    ) {
        $gitDir = rtrim($this->repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new InvalidRepositoryException($this->repoPath);
        }
        $this->cache = $cache ?? \app(GitCacheService::class);
        $this->commandRunner = new GitCommandRunner($this->repoPath);
    }
}
