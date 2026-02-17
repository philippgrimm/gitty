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
        $gitDir = rtrim($this->repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
        $this->cache = new GitCacheService;
    }

    public function status(): GitStatus
    {
        return $this->cache->get(
            $this->repoPath,
            'status',
            function () {
                $result = Process::path($this->repoPath)->run('git status --porcelain=v2 --branch');

                return GitStatus::fromOutput($result->output());
            },
            5
        );
    }

    public function log(int $limit = 100, ?string $branch = null, bool $detailed = false): Collection
    {
        $cacheKey = "log:{$limit}:".($branch ?? 'HEAD').':'.($detailed ? 'detailed' : 'oneline');

        return $this->cache->get(
            $this->repoPath,
            $cacheKey,
            function () use ($limit, $branch, $detailed) {
                if ($detailed) {
                    $command = "git log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n {$limit}";
                } else {
                    $command = "git log --oneline -n {$limit}";
                }

                if ($branch !== null) {
                    $command .= " {$branch}";
                }

                $result = Process::path($this->repoPath)->run($command);
                $lines = array_filter(explode("\n", trim($result->output())));

                if ($detailed) {
                    return collect($lines)->map(fn ($line) => $this->parseDetailedLogLine($line));
                }

                return collect($lines)->map(fn ($line) => Commit::fromLogLine($line));
            },
            60
        );
    }

    private function parseDetailedLogLine(string $line): Commit
    {
        $parts = explode('|||', $line);

        $sha = $parts[0] ?? '';
        $author = $parts[1] ?? '';
        $email = $parts[2] ?? '';
        $date = $parts[3] ?? '';
        $message = $parts[4] ?? '';
        $refString = $parts[5] ?? '';

        $refs = [];
        if (! empty($refString)) {
            $refs = array_map('trim', explode(',', $refString));
        }

        return new Commit(
            sha: $sha,
            shortSha: substr($sha, 0, 7),
            message: $message,
            author: $author,
            email: $email,
            date: $date,
            refs: $refs,
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
        $diffResult = DiffResult::fromDiffOutput($result->output());

        // For untracked files, git diff returns empty — use --no-index instead
        if ($diffResult->files->isEmpty() && $file !== null && ! $staged) {
            $statusResult = Process::path($this->repoPath)->run("git status --porcelain=v2 -- {$file}");
            if (str_starts_with(trim($statusResult->output()), '?')) {
                $untrackedResult = Process::path($this->repoPath)->run("git diff --no-index -- /dev/null {$file}");

                return DiffResult::fromDiffOutput($untrackedResult->output());
            }
        }

        return $diffResult;
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
