<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\Commit;
use App\DTOs\DiffResult;
use App\DTOs\GitStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class GitService
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

    public function status(): GitStatus
    {
        return $this->cache->get(
            $this->repoPath,
            'status',
            function () {
                $result = Process::path($this->repoPath)->run('git status --porcelain=v2');

                return GitStatus::fromOutput($result->output());
            },
            5
        );
    }

    public function log(int $limit = 100, ?string $branch = null): Collection
    {
        $cacheKey = "log:{$limit}:" . ($branch ?? 'HEAD');

        return $this->cache->get(
            $this->repoPath,
            $cacheKey,
            function () use ($limit, $branch) {
                $command = "git log --oneline -n {$limit}";
                if ($branch !== null) {
                    $command .= " {$branch}";
                }

                $result = Process::path($this->repoPath)->run($command);
                $lines = array_filter(explode("\n", trim($result->output())));

                return collect($lines)->map(fn ($line) => Commit::fromLogLine($line));
            },
            60
        );
    }

    public function diff(?string $file = null, bool $staged = false): DiffResult
    {
        $command = 'git diff';
        if ($staged) {
            $command .= ' --cached';
        }
        if ($file !== null) {
            $command .= " -- {$file}";
        }

        $result = Process::path($this->repoPath)->run($command);

        return DiffResult::fromDiffOutput($result->output());
    }

    public function currentBranch(): string
    {
        $status = $this->status();

        return $status->branch;
    }

    public function isDetachedHead(): bool
    {
        return $this->currentBranch() === '(detached)';
    }

    public function aheadBehind(): array
    {
        $status = $this->status();

        return $status->aheadBehind;
    }
}
