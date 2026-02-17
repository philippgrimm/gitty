<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\Exceptions\GitConflictException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class RebaseService extends AbstractGitService
{
    public function isRebasing(): bool
    {
        $rebaseMergePath = rtrim($this->repoPath, '/').'/.git/rebase-merge';
        $rebaseApplyPath = rtrim($this->repoPath, '/').'/.git/rebase-apply';

        return is_dir($rebaseMergePath) || is_dir($rebaseApplyPath);
    }

    public function getRebaseCommits(string $onto, int $count): Collection
    {
        $result = $this->commandRunner->run("log --oneline HEAD~{$count}..HEAD");

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Failed to get rebase commits: '.$result->errorOutput());
        }

        $lines = array_filter(explode("\n", trim($result->output())));

        return collect($lines)->map(function (string $line) {
            $parts = explode(' ', $line, 2);

            return [
                'sha' => $parts[0] ?? '',
                'shortSha' => $parts[0] ?? '',
                'message' => $parts[1] ?? '',
                'action' => 'pick',
            ];
        })->reverse()->values();
    }

    public function startRebase(string $onto, array $plan): void
    {
        // Generate rebase-todo content
        $todoLines = collect($plan)->map(function (array $commit) {
            $action = $commit['action'] ?? 'pick';
            $sha = $commit['sha'] ?? '';

            return "{$action} {$sha}";
        })->join("\n");

        // Create temporary file for the todo list
        $todoFile = tempnam(sys_get_temp_dir(), 'gitty-rebase-todo-');
        file_put_contents($todoFile, $todoLines."\n");

        // Use GIT_SEQUENCE_EDITOR to inject our todo list
        $result = Process::path($this->repoPath)
            ->env(['GIT_SEQUENCE_EDITOR' => "cp {$todoFile}"])
            ->run("git rebase -i {$onto}");

        // Clean up temp file
        @unlink($todoFile);

        if ($result->exitCode() !== 0) {
            $errorOutput = $result->errorOutput();
            if (str_contains($errorOutput, 'conflict')) {
                throw new GitConflictException('Rebase');
            }
            throw new \RuntimeException('Git rebase failed: '.$errorOutput);
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function continueRebase(): void
    {
        $result = $this->commandRunner->run('rebase --continue');

        if ($result->exitCode() !== 0) {
            $errorOutput = $result->errorOutput();
            if (str_contains($errorOutput, 'conflict')) {
                throw new GitConflictException('Rebase');
            }
            throw new \RuntimeException('Git rebase --continue failed: '.$errorOutput);
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function abortRebase(): void
    {
        $result = $this->commandRunner->run('rebase --abort');

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Failed to abort rebase: '.$result->errorOutput());
        }

        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'history');
        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }
}
