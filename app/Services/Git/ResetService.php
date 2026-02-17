<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\Exceptions\GitConflictException;

class ResetService extends AbstractGitService
{
    public function resetSoft(string $commitSha): void
    {
        $result = $this->commandRunner->run('reset --soft', [$commitSha]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git reset --soft failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function resetMixed(string $commitSha): void
    {
        $result = $this->commandRunner->run('reset', [$commitSha]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git reset failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function resetHard(string $commitSha): void
    {
        $result = $this->commandRunner->run('reset --hard', [$commitSha]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git reset --hard failed: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function revertCommit(string $commitSha): void
    {
        $result = $this->commandRunner->run('revert --no-edit', [$commitSha]);

        if ($result->exitCode() !== 0) {
            $errorOutput = $result->errorOutput();
            if (str_contains($errorOutput, 'conflict')) {
                throw new GitConflictException('Revert');
            }
            throw new \RuntimeException('Git revert failed: '.$errorOutput);
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }
}
