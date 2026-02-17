<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\ConflictFile;
use Illuminate\Support\Collection;

class ConflictService extends AbstractGitService
{
    public function isInMergeState(): bool
    {
        $mergeHeadPath = rtrim($this->repoPath, '/').'/.git/MERGE_HEAD';

        return file_exists($mergeHeadPath);
    }

    public function getConflictedFiles(): Collection
    {
        $result = $this->commandRunner->run('status --porcelain=v2');
        $lines = explode("\n", trim($result->output()));

        $conflictedFiles = collect();

        foreach ($lines as $line) {
            if (str_starts_with($line, 'u ')) {
                // Unmerged entry: u <XY> <sub> <m1> <m2> <m3> <mW> <h1> <h2> <h3> <path>
                $parts = preg_split('/\s+/', $line, 11);
                $path = $parts[10] ?? '';
                $status = $parts[1] ?? 'UU';

                if (! empty($path)) {
                    $conflictedFiles->push([
                        'path' => $path,
                        'status' => $status,
                    ]);
                }
            }
        }

        return $conflictedFiles;
    }

    public function getConflictVersions(string $file): ConflictFile
    {
        // Check if file is binary
        $isBinary = $this->isBinaryFile($file);

        // Get the three versions
        $baseContent = $this->getFileVersion($file, 1); // :1: = common ancestor
        $oursContent = $this->getFileVersion($file, 2); // :2: = current branch (ours)
        $theirsContent = $this->getFileVersion($file, 3); // :3: = incoming branch (theirs)

        // Determine status
        $status = $this->getConflictStatus($file);

        return new ConflictFile(
            path: $file,
            status: $status,
            oursContent: $oursContent,
            theirsContent: $theirsContent,
            baseContent: $baseContent,
            isBinary: $isBinary,
        );
    }

    public function resolveConflict(string $file, string $resolvedContent): void
    {
        // Write resolved content to file
        $filePath = rtrim($this->repoPath, '/').'/'.$file;
        file_put_contents($filePath, $resolvedContent);

        // Stage the resolved file
        $result = $this->commandRunner->run('add', [$file]);

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Failed to stage resolved file: '.$result->errorOutput());
        }

        // Invalidate status cache
        $this->cache->invalidateGroup($this->repoPath, 'status');
    }

    public function abortMerge(): void
    {
        $result = $this->commandRunner->run('merge --abort');

        if ($result->exitCode() !== 0) {
            throw new \RuntimeException('Failed to abort merge: '.$result->errorOutput());
        }

        // Invalidate caches
        $this->cache->invalidateGroup($this->repoPath, 'status');
        $this->cache->invalidateGroup($this->repoPath, 'branches');
    }

    public function getMergeHeadBranch(): string
    {
        // Try to read MERGE_MSG for branch name hint
        $mergeMsgPath = rtrim($this->repoPath, '/').'/.git/MERGE_MSG';
        if (file_exists($mergeMsgPath)) {
            $mergeMsg = file_get_contents($mergeMsgPath);
            // Parse "Merge branch 'feature-name'" or similar
            if (preg_match("/Merge branch '([^']+)'/", $mergeMsg, $matches)) {
                return $matches[1];
            }
        }

        // Fallback: get commit message from MERGE_HEAD
        $result = $this->commandRunner->run('log -1 --format=%s MERGE_HEAD');
        if ($result->successful()) {
            return trim($result->output());
        }

        return 'unknown';
    }

    private function getFileVersion(string $file, int $stage): string
    {
        $result = $this->commandRunner->run('show', [":{$stage}:{$file}"]);

        // If the stage doesn't exist (e.g., file was added in one branch), return empty
        if ($result->exitCode() !== 0) {
            return '';
        }

        return $result->output();
    }

    private function isBinaryFile(string $file): bool
    {
        // Use git diff --numstat to detect binary files (shows "- -" for binary)
        $result = $this->commandRunner->run('diff --numstat HEAD --', [$file]);

        if ($result->exitCode() !== 0) {
            return false;
        }

        $output = trim($result->output());

        // Binary files show as "- -" in numstat
        return str_starts_with($output, '- -');
    }

    private function getConflictStatus(string $file): string
    {
        $result = $this->commandRunner->run('status --porcelain=v2');
        $lines = explode("\n", trim($result->output()));

        foreach ($lines as $line) {
            if (str_starts_with($line, 'u ')) {
                $parts = preg_split('/\s+/', $line, 11);
                $path = $parts[10] ?? '';
                $status = $parts[1] ?? 'UU';

                if ($path === $file) {
                    return $status;
                }
            }
        }

        return 'UU'; // Default to both modified
    }
}
