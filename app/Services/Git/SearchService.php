<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Collection;

class SearchService extends AbstractGitService
{
    /**
     * Search commit messages using git log --grep
     */
    public function searchCommits(string $query, int $limit = 50): Collection
    {
        if (empty(trim($query))) {
            return collect();
        }

        $result = $this->commandRunner->run("log --format=\"%H|%h|%an|%ar|%s\" -{$limit} --grep", [$query]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git log --grep failed: '.$result->errorOutput());
        }

        return $this->parseCommitOutput($result->output());
    }

    /**
     * Search file content using git log -S (pickaxe)
     */
    public function searchContent(string $query, int $limit = 50): Collection
    {
        if (empty(trim($query))) {
            return collect();
        }

        $result = $this->commandRunner->run("log --format=\"%H|%h|%an|%ar|%s\" -{$limit} -S", [$query]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git log -S failed: '.$result->errorOutput());
        }

        return $this->parseCommitOutput($result->output());
    }

    /**
     * Search filenames using git ls-files
     */
    public function searchFiles(string $query): Collection
    {
        if (empty(trim($query))) {
            return collect();
        }

        $result = $this->commandRunner->run('ls-files', ["*{$query}*"]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git ls-files failed: '.$result->errorOutput());
        }

        return $this->parseFileOutput($result->output());
    }

    private function parseCommitOutput(string $output): Collection
    {
        $lines = array_filter(explode("\n", trim($output)));

        return collect($lines)->map(function (string $line): array {
            $parts = explode('|', $line, 5);

            return [
                'sha' => $parts[0] ?? '',
                'shortSha' => $parts[1] ?? '',
                'author' => $parts[2] ?? '',
                'date' => $parts[3] ?? '',
                'message' => $parts[4] ?? '',
            ];
        });
    }

    private function parseFileOutput(string $output): Collection
    {
        $lines = array_filter(explode("\n", trim($output)));

        return collect($lines)->map(function (string $line): array {
            return [
                'path' => $line,
            ];
        });
    }
}
