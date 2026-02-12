<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Stash;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class StashService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function stash(string $message, bool $includeUntracked): void
    {
        $command = 'git stash push';
        if ($includeUntracked) {
            $command .= ' -u';
        }
        $command .= " -m \"{$message}\"";

        Process::path($this->repoPath)->run($command);
    }

    public function stashList(): Collection
    {
        $result = Process::path($this->repoPath)->run('git stash list');
        $lines = array_filter(explode("\n", trim($result->output())));

        return collect($lines)->map(fn ($line) => Stash::fromStashLine($line));
    }

    public function stashApply(int $index): void
    {
        Process::path($this->repoPath)->run("git stash apply stash@{{$index}}");
    }

    public function stashPop(int $index): void
    {
        Process::path($this->repoPath)->run("git stash pop stash@{{$index}}");
    }

    public function stashDrop(int $index): void
    {
        Process::path($this->repoPath)->run("git stash drop stash@{{$index}}");
    }
}
