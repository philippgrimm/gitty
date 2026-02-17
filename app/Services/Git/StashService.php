<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Stash;
use Illuminate\Support\Collection;

class StashService extends AbstractGitService
{
    public function stash(string $message, bool $includeUntracked): void
    {
        $subcommand = $includeUntracked ? 'stash push -u -m' : 'stash push -m';
        $this->commandRunner->runOrFail($subcommand, [$message], 'Git stash failed');

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stashList(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'stashes',
            function () {
                $result = $this->commandRunner->run('stash list');
                $lines = array_filter(explode("\n", trim($result->output())));

                return collect($lines)->map(fn ($line) => Stash::fromStashLine($line));
            },
            30
        );
    }

    public function stashApply(int $index): void
    {
        $this->commandRunner->runOrFail("stash apply stash@{{$index}}", [], 'Git stash apply failed');

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function tryStashApply(int $index): bool
    {
        $result = $this->commandRunner->run("stash apply stash@{{$index}}");

        $this->cache->invalidateGroup($this->repoPath, 'status');

        return $result->successful();
    }

    public function stashPop(int $index): void
    {
        $this->commandRunner->runOrFail("stash pop stash@{{$index}}", [], 'Git stash pop failed');

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stashDrop(int $index): void
    {
        $this->commandRunner->runOrFail("stash drop stash@{{$index}}", [], 'Git stash drop failed');

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
    }

    public function stashFiles(array $paths): void
    {
        if (empty($paths)) {
            throw new \InvalidArgumentException('Cannot stash empty file list');
        }

        $message = $this->generateStashMessage($paths);
        $args = array_merge([$message, '--'], $paths);
        $this->commandRunner->runOrFail('stash push -u -m', $args, 'Git stash files failed');

        $this->cache->invalidateGroup($this->repoPath, 'stashes');
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    private function generateStashMessage(array $paths): string
    {
        if (count($paths) <= 3) {
            $basenames = array_map(fn ($path) => basename($path), $paths);

            return 'Stash: '.implode(', ', $basenames);
        }

        $result = $this->commandRunner->run('rev-parse --abbrev-ref HEAD');
        $branch = trim($result->output());

        return 'Stash: '.count($paths).' files on '.$branch;
    }
}
