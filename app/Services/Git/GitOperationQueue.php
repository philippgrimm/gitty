<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\Exceptions\GitOperationInProgressException;
use Illuminate\Support\Facades\Cache;

class GitOperationQueue
{
    protected string $lockKey;

    public function __construct(
        protected string $repoPath,
    ) {
        $this->lockKey = 'git-op-' . md5($this->repoPath);
    }

    public function execute(callable $operation): mixed
    {
        $lock = Cache::lock($this->lockKey, 30);

        if (! $lock->get()) {
            throw new GitOperationInProgressException($this->repoPath);
        }

        try {
            return $operation();
        } finally {
            $lock->release();
        }
    }

    public function isLocked(): bool
    {
        $lock = Cache::lock($this->lockKey, 0);

        if ($lock->get()) {
            $lock->release();

            return false;
        }

        return true;
    }
}
