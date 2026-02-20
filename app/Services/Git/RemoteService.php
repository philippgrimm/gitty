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

    public function push(string $remote, string $branch): string
    {
        $result = $this->commandRunner->runOrFail('push', [$remote, $branch], 'Git push failed');

        $this->cache->invalidateGroup($this->repoPath, 'branches');

        return $result->output();
    }

    public function pull(string $remote, string $branch): string
    {
        $result = $this->commandRunner->runOrFail('pull', [$remote, $branch], 'Git pull failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
        $this->cache->invalidateGroup($this->repoPath, 'branches');

        return $result->output();
    }

    public function fetch(string $remote): string
    {
        $result = $this->commandRunner->runOrFail('fetch', [$remote], 'Git fetch failed');

        $this->invalidateRemoteGroups();

        return $result->output();
    }

    public function fetchAll(): string
    {
        $result = $this->commandRunner->runOrFail('fetch --all', [], 'Git fetch all failed');

        $this->invalidateRemoteGroups();

        return $result->output();
    }

    public function pushSetUpstream(string $remote, string $branch): string
    {
        $result = $this->commandRunner->runOrFail('push -u', [$remote, $branch], 'Git push failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'branches');

        return $result->output();
    }

    public function forcePushWithLease(string $remote, string $branch): string
    {
        $result = $this->commandRunner->runOrFail('push --force-with-lease', [$remote, $branch], 'Git force push failed');

        $this->cache->invalidateGroup($this->repoPath, 'branches');

        return $result->output();
    }

    private function invalidateRemoteGroups(): void
    {
        $this->cache->invalidateGroup($this->repoPath, 'branches');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }
}
