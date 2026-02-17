<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\AheadBehind;
use App\DTOs\Commit;
use App\DTOs\DiffResult;
use App\DTOs\GitStatus;
use Illuminate\Support\Collection;

class GitService extends AbstractGitService
{
    public function status(): GitStatus
    {
        return $this->cache->get(
            $this->repoPath,
            'status',
            function () {
                $result = $this->commandRunner->run('status --porcelain=v2 --branch');

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
                    $command = "log --format='%H|||%an|||%ae|||%ar|||%s|||%D' -n {$limit}";
                } else {
                    $command = "log --oneline -n {$limit}";
                }

                $args = [];
                if ($branch !== null) {
                    $args = [$branch];
                }

                $result = $this->commandRunner->run($command, $args);
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
        $command = 'diff';
        if ($staged) {
            $command .= ' --cached';
        }
        if ($file !== null) {
            $command .= ' --';
        }

        $args = [];
        if ($file !== null) {
            $args = [$file];
        }

        $result = $this->commandRunner->run($command, $args);
        $diffResult = DiffResult::fromDiffOutput($result->output());

        // For untracked files, git diff returns empty — use --no-index instead
        if ($diffResult->files->isEmpty() && $file !== null && ! $staged) {
            $statusResult = $this->commandRunner->run('status --porcelain=v2 --', [$file]);
            if (str_starts_with(trim($statusResult->output()), '?')) {
                $untrackedResult = $this->commandRunner->run('diff --no-index --', ['/dev/null', $file]);

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

    public function aheadBehind(): AheadBehind
    {
        $status = $this->status();

        return $status->aheadBehind;
    }

    public function getTrackedFileSize(string $file): int
    {
        $result = $this->commandRunner->run('cat-file -s', ["HEAD:{$file}"]);

        if (! $result->successful()) {
            return 0;
        }

        return (int) trim($result->output());
    }

    public function getFileContentAtHead(string $file): ?string
    {
        $result = $this->commandRunner->run('show', ["HEAD:{$file}"]);

        if (! $result->successful() || empty($result->output())) {
            return null;
        }

        return $result->output();
    }

    public function getConfigValue(string $key): ?string
    {
        $result = $this->commandRunner->run('config --get', [$key]);

        if (! $result->successful() || empty(trim($result->output()))) {
            return null;
        }

        return trim($result->output());
    }
}
