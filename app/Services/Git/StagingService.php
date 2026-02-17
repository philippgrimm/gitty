<?php

declare(strict_types=1);

namespace App\Services\Git;

class StagingService extends AbstractGitService
{
    public function stageFile(string $file): void
    {
        $this->commandRunner->run('add', [$file]);

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function unstageFile(string $file): void
    {
        $this->commandRunner->run('reset HEAD', [$file]);

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stageAll(): void
    {
        $this->commandRunner->run('add .');

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function unstageAll(): void
    {
        $this->commandRunner->run('reset HEAD');

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function discardFile(string $file): void
    {
        $this->commandRunner->run('checkout --', [$file]);

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function discardAll(): void
    {
        $this->commandRunner->run('checkout -- .');

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stageFiles(array $files): void
    {
        if (empty($files)) {
            throw new \InvalidArgumentException('Cannot stage empty file list');
        }

        $this->commandRunner->run('add', $files);

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function unstageFiles(array $files): void
    {
        if (empty($files)) {
            throw new \InvalidArgumentException('Cannot unstage empty file list');
        }

        $this->commandRunner->run('reset HEAD --', $files);

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function discardFiles(array $files): void
    {
        if (empty($files)) {
            throw new \InvalidArgumentException('Cannot discard empty file list');
        }

        $this->commandRunner->run('checkout --', $files);

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }
}
