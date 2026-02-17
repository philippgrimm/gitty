<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\BlameLine;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class BlameService
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

    public function blame(string $file): Collection
    {
        $result = Process::path($this->repoPath)->run("git blame --porcelain \"{$file}\"");

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Git blame failed: '.$result->errorOutput());
        }

        return $this->parsePorcelainOutput($result->output());
    }

    private function parsePorcelainOutput(string $output): Collection
    {
        $lines = explode("\n", $output);
        $blameLines = collect();

        $currentSha = '';
        $currentAuthor = '';
        $currentDate = '';
        $currentLineNumber = 0;

        $i = 0;
        $count = count($lines);

        while ($i < $count) {
            $line = $lines[$i];

            if (preg_match('/^([0-9a-f]{40})\s+(\d+)\s+(\d+)/', $line, $matches)) {
                $currentSha = $matches[1];
                $currentLineNumber = (int) $matches[3];
                $i++;

                while ($i < $count && ! str_starts_with($lines[$i], "\t")) {
                    $metaLine = $lines[$i];

                    if (str_starts_with($metaLine, 'author ')) {
                        $currentAuthor = substr($metaLine, 7);
                    } elseif (str_starts_with($metaLine, 'author-time ')) {
                        $timestamp = (int) substr($metaLine, 12);
                        $currentDate = $this->formatRelativeDate($timestamp);
                    }

                    $i++;
                }

                if ($i < $count && str_starts_with($lines[$i], "\t")) {
                    $content = substr($lines[$i], 1);

                    $blameLines->push(new BlameLine(
                        commitSha: $currentSha,
                        author: $currentAuthor,
                        date: $currentDate,
                        lineNumber: $currentLineNumber,
                        content: $content,
                    ));
                }
            }

            $i++;
        }

        return $blameLines;
    }

    private function formatRelativeDate(int $timestamp): string
    {
        $diff = time() - $timestamp;

        if ($diff < 60) {
            return 'just now';
        }
        if ($diff < 3600) {
            $mins = (int) floor($diff / 60);

            return $mins.' min'.($mins > 1 ? 's' : '').' ago';
        }
        if ($diff < 86400) {
            $hours = (int) floor($diff / 3600);

            return $hours.' hour'.($hours > 1 ? 's' : '').' ago';
        }
        if ($diff < 2592000) {
            $days = (int) floor($diff / 86400);

            return $days.' day'.($days > 1 ? 's' : '').' ago';
        }
        if ($diff < 31536000) {
            $months = (int) floor($diff / 2592000);

            return $months.' month'.($months > 1 ? 's' : '').' ago';
        }

        $years = (int) floor($diff / 31536000);

        return $years.' year'.($years > 1 ? 's' : '').' ago';
    }
}
