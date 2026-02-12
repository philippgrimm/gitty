<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Remote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class RemoteService
{
    private GitCacheService $cache;

    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
        $this->cache = new GitCacheService();
    }

    public function remotes(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'remotes',
            function () {
                $result = Process::path($this->repoPath)->run('git remote -v');
                $lines = array_filter(explode("\n", trim($result->output())));

                $remotes = Remote::fromRemoteLines($lines);

                return collect($remotes);
            },
            300
        );
    }

    public function push(string $remote, string $branch): void
    {
        Process::path($this->repoPath)->run("git push {$remote} {$branch}");

        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function pull(string $remote, string $branch): void
    {
        Process::path($this->repoPath)->run("git pull {$remote} {$branch}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function fetch(string $remote): void
    {
        Process::path($this->repoPath)->run("git fetch {$remote}");

        $this->cache->invalidateGroup($this->repoPath, 'branches');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }

    public function fetchAll(): void
    {
        Process::path($this->repoPath)->run('git fetch --all');

        $this->cache->invalidateGroup($this->repoPath, 'branches');
        $this->cache->invalidateGroup($this->repoPath, 'history');
    }
}
