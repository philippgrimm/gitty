<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Process;

class ResetService
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

    public function resetSoft(string $commitSha): void
    {
        $result = Process::path($this->repoPath)->run("git reset --soft {$commitSha}");

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git reset --soft failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function resetMixed(string $commitSha): void
    {
        $result = Process::path($this->repoPath)->run("git reset {$commitSha}");

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git reset failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function resetHard(string $commitSha): void
    {
        $result = Process::path($this->repoPath)->run("git reset --hard {$commitSha}");

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git reset --hard failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function revertCommit(string $commitSha): void
    {
        $result = Process::path($this->repoPath)->run("git revert {$commitSha} --no-edit");

        if ($result->exitCode() !== 0) {
            $errorOutput = $result->errorOutput();
            if (str_contains($errorOutput, 'conflict')) {
                throw new \RuntimeException('Revert failed due to conflicts. Please resolve conflicts manually.');
            }
            throw new \RuntimeException('Git revert failed: '.$errorOutput);
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }
}
