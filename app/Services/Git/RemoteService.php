<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Remote;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class RemoteService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function remotes(): Collection
    {
        $result = Process::path($this->repoPath)->run('git remote -v');
        $lines = array_filter(explode("\n", trim($result->output())));

        $remotes = Remote::fromRemoteLines($lines);

        return collect($remotes);
    }

    public function push(string $remote, string $branch): void
    {
        Process::path($this->repoPath)->run("git push {$remote} {$branch}");
    }

    public function pull(string $remote, string $branch): void
    {
        Process::path($this->repoPath)->run("git pull {$remote} {$branch}");
    }

    public function fetch(string $remote): void
    {
        Process::path($this->repoPath)->run("git fetch {$remote}");
    }

    public function fetchAll(): void
    {
        Process::path($this->repoPath)->run('git fetch --all');
    }
}
