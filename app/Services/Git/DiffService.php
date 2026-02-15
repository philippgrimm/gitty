<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\DiffFile;
use App\DTOs\DiffResult;
use App\DTOs\Hunk;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

class DiffService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function parseDiff(string $rawDiff): DiffResult
    {
        return DiffResult::fromDiffOutput($rawDiff);
    }

    public function extractHunks(DiffFile $file): Collection
    {
        return $file->hunks;
    }

    public function stageHunk(DiffFile $file, Hunk $hunk): void
    {
        $patch = $this->generatePatch($file, $hunk);
        $process = Process::path($this->repoPath)->input($patch);
        $process->run('git apply --cached');
    }

    public function unstageHunk(DiffFile $file, Hunk $hunk): void
    {
        $patch = $this->generatePatch($file, $hunk);
        $process = Process::path($this->repoPath)->input($patch);
        $process->run('git apply --cached --reverse');
    }

    protected function generatePatch(DiffFile $file, Hunk $hunk): string
    {
        $patch = "diff --git a/{$file->oldPath} b/{$file->newPath}\n";
        $patch .= "--- a/{$file->oldPath}\n";
        $patch .= "+++ b/{$file->newPath}\n";
        $patch .= "@@ -{$hunk->oldStart},{$hunk->oldCount} +{$hunk->newStart},{$hunk->newCount} @@ {$hunk->header}\n";

        foreach ($hunk->lines as $line) {
            $prefix = match ($line->type) {
                'addition' => '+',
                'deletion' => '-',
                default => ' ',
            };
            $patch .= $prefix.$line->content."\n";
        }

        return $patch;
    }
}
