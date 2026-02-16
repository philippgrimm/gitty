<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Stash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class StashService
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

    public function stash(string $message, bool $includeUntracked): void
    {
        $command = 'git stash push';
        if ($includeUntracked) {
            $command .= ' -u';
        }
        $command .= " -m \"{$message}\"";

        Process::path($this->repoPath)->run($command);

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stashList(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'stashes',
            function () {
                $result = Process::path($this->repoPath)->run('git stash list');
                $lines = array_filter(explode("\n", trim($result->output())));

                return collect($lines)->map(fn ($line) => Stash::fromStashLine($line));
            },
            30
        );
    }

    public function stashApply(int $index): void
    {
        Process::path($this->repoPath)->run("git stash apply stash@{{$index}}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stashPop(int $index): void
    {
        Process::path($this->repoPath)->run("git stash pop stash@{{$index}}");

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stashDrop(int $index): void
    {
        Process::path($this->repoPath)->run("git stash drop stash@{{$index}}");

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
    }

    public function stashFiles(array $paths): void
    {
        if (empty($paths)) {
            throw new \InvalidArgumentException('Cannot stash empty file list');
        }

        $message = $this->generateStashMessage($paths);
        $escapedPaths = array_map(fn ($path) => escapeshellarg($path), $paths);
        $pathsString = implode(' ', $escapedPaths);

        $command = "git stash push -u -m \"{$message}\" -- {$pathsString}";

        Process::path($this->repoPath)->run($command);

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    private function generateStashMessage(array $paths): string
    {
        if (count($paths) <= 3) {
            $basenames = array_map(fn ($path) => basename($path), $paths);

            return 'Stash: '.implode(', ', $basenames);
        }

        $result = Process::path($this->repoPath)->run('git rev-parse --abbrev-ref HEAD');
        $branch = trim($result->output());

        return 'Stash: '.count($paths).' files on '.$branch;
    }
}
