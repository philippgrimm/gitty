<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Remote;
use Illuminate\Support\Collection;

class RemoteService extends AbstractGitService
{
    public function remotes(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'remotes',
            function () {
                $result = $this->commandRunner->run('remote -v');
                $lines = array_filter(explode("\n", trim($result->output())));

                $remotes = Remote::fromRemoteLines($lines);

                return collect($remotes);
            },
            300
        );
    }

    public function push(string $remote, string $branch): void
    {
        $this->commandRunner->run('push', [$remote, $branch]);

        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function pull(string $remote, string $branch): void
    {
        $this->commandRunner->run('pull', [$remote, $branch]);

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function fetch(string $remote): void
    {
        $this->commandRunner->run('fetch', [$remote]);

        $this->invalidateRemoteGroups();
    }

    public function fetchAll(): void
    {
        $this->commandRunner->run('fetch --all');

        $this->invalidateRemoteGroups();
    }

    private function invalidateRemoteGroups(): void
    {
        $this->cache->invalidateGroup($this->repoPath, 'branches');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }
}
