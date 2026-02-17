<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\DiffFile;
use App\DTOs\DiffResult;
use App\DTOs\Hunk;
use Illuminate\Support\Collection;

class DiffService extends AbstractGitService
{
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
        $this->commandRunner->runWithInput('apply --cached', $patch);
    }

    public function unstageHunk(DiffFile $file, Hunk $hunk): void
    {
        $patch = $this->generatePatch($file, $hunk);
        $this->commandRunner->runWithInput('apply --cached --reverse', $patch);
    }

    public function stageLines(DiffFile $file, Hunk $hunk, array $selectedLineIndices): void
    {
        $patch = $this->generateLinePatch($file, $hunk, $selectedLineIndices);
        $result = $this->commandRunner->runWithInput('apply --cached --unidiff-zero -', $patch);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to stage lines: '.$result->errorOutput());
        }
    }

    public function unstageLines(DiffFile $file, Hunk $hunk, array $selectedLineIndices): void
    {
        $patch = $this->generateLinePatch($file, $hunk, $selectedLineIndices);
        $result = $this->commandRunner->runWithInput('apply --cached --unidiff-zero --reverse -', $patch);

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to unstage lines: '.$result->errorOutput());
        }
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

    protected function generateLinePatch(DiffFile $file, Hunk $hunk, array $selectedLineIndices): string
    {
        // Build patch with only selected lines
        $patch = "diff --git a/{$file->oldPath} b/{$file->newPath}\n";
        $patch .= "--- a/{$file->oldPath}\n";
        $patch .= "+++ b/{$file->newPath}\n";

        // Calculate new line counts based on selected lines
        $oldCount = 0;
        $newCount = 0;
        $patchLines = [];

        foreach ($hunk->lines as $index => $line) {
            $isSelected = in_array($index, $selectedLineIndices, true);

            if ($line->type === 'context') {
                // Context lines always included
                $oldCount++;
                $newCount++;
                $patchLines[] = ' '.$line->content;
            } elseif ($line->type === 'addition') {
                if ($isSelected) {
                    // Selected additions: include as additions
                    $newCount++;
                    $patchLines[] = '+'.$line->content;
                } else {
                    // Unselected additions: convert to context
                    $oldCount++;
                    $newCount++;
                    $patchLines[] = ' '.$line->content;
                }
            } elseif ($line->type === 'deletion') {
                if ($isSelected) {
                    // Selected deletions: include as deletions
                    $oldCount++;
                    $patchLines[] = '-'.$line->content;
                }
                // Unselected deletions: omit entirely
            }
        }

        // Build hunk header with recalculated counts
        $patch .= "@@ -{$hunk->oldStart},{$oldCount} +{$hunk->newStart},{$newCount} @@ {$hunk->header}\n";

        // Add all patch lines
        foreach ($patchLines as $line) {
            $patch .= $line."\n";
        }

        return $patch;
    }
}
