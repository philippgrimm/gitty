<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Process;

class StagingService
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

    public function stageFile(string $file): void
    {
        Process::path($this->repoPath)->run("git add {$file}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function unstageFile(string $file): void
    {
        Process::path($this->repoPath)->run("git reset HEAD {$file}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stageAll(): void
    {
        Process::path($this->repoPath)->run('git add .');

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function unstageAll(): void
    {
        Process::path($this->repoPath)->run('git reset HEAD');

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function discardFile(string $file): void
    {
        Process::path($this->repoPath)->run("git checkout -- {$file}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function discardAll(): void
    {
        Process::path($this->repoPath)->run('git checkout -- .');

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function stageFiles(array $files): void
    {
        if (empty($files)) {
            throw new \InvalidArgumentException('Cannot stage empty file list');
        }

        $escapedFiles = array_map(fn ($file) => escapeshellarg($file), $files);
        $filesString = implode(' ', $escapedFiles);

        Process::path($this->repoPath)->run("git add {$filesString}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function unstageFiles(array $files): void
    {
        if (empty($files)) {
            throw new \InvalidArgumentException('Cannot unstage empty file list');
        }

        $escapedFiles = array_map(fn ($file) => escapeshellarg($file), $files);
        $filesString = implode(' ', $escapedFiles);

        Process::path($this->repoPath)->run("git reset HEAD -- {$filesString}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function discardFiles(array $files): void
    {
        if (empty($files)) {
            throw new \InvalidArgumentException('Cannot discard empty file list');
        }

        $escapedFiles = array_map(fn ($file) => escapeshellarg($file), $files);
        $filesString = implode(' ', $escapedFiles);

        Process::path($this->repoPath)->run("git checkout -- {$filesString}");

        $this->cache->invalidateGroup($this->repoPath, 'status');
    }
}
