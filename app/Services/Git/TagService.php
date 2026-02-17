<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class TagService
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

    public function tags(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'tags',
            function () {
                $result = Process::path($this->repoPath)
                    ->run("git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'");

                if (! $result->successful()) {
                    return collect();
                }

                return collect(array_filter(explode("\n", trim($result->output()))))
                    ->map(function (string $line) {
                        $parts = explode('|||', $line);

                        return [
                            'name' => $parts[0] ?? '',
                            'sha' => $parts[1] ?? '',
                            'date' => $parts[2] ?? '',
                            'message' => $parts[3] ?? '',
                        ];
                    });
            },
            60
        );
    }

    public function createTag(string $name, ?string $message = null, ?string $commit = null): void
    {
        $command = $message
            ? "git tag -a \"{$name}\" -m \"{$message}\""
            : "git tag \"{$name}\"";

        if ($commit) {
            $command .= " {$commit}";
        }

        $result = Process::path($this->repoPath)->run($command);
        if (! $result->successful()) {
            throw new \RuntimeException('Failed to create tag: '.$result->errorOutput());
        }
        $this->cache->invalidateGroup($this->repoPath, 'tags');
    }

    public function deleteTag(string $name): void
    {
        $result = Process::path($this->repoPath)->run("git tag -d \"{$name}\"");
        if (! $result->successful()) {
            throw new \RuntimeException('Failed to delete tag: '.$result->errorOutput());
        }
        $this->cache->invalidateGroup($this->repoPath, 'tags');
    }

    public function pushTag(string $name, string $remote = 'origin'): void
    {
        $result = Process::path($this->repoPath)->run("git push {$remote} \"{$name}\"");
        if (! $result->successful()) {
            throw new \RuntimeException('Failed to push tag: '.$result->errorOutput());
        }
    }
}
