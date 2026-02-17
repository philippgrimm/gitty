<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

readonly class GitStatus
{
    public function __construct(
        public string $branch,
        public ?string $upstream,
        public AheadBehind $aheadBehind,
        /** @var Collection<int, ChangedFile> */
        public Collection $changedFiles,
    ) {}

    public static function fromOutput(string $output): self
    {
        $lines = explode("\n", trim($output));
        $branch = '';
        $upstream = null;
        $ahead = 0;
        $behind = 0;
        $changedFiles = collect();

        foreach ($lines as $line) {
            if (str_starts_with($line, '# branch.head ')) {
                $branch = trim(substr($line, 14));
            } elseif (str_starts_with($line, '# branch.upstream ')) {
                $upstream = trim(substr($line, 18));
            } elseif (str_starts_with($line, '# branch.ab ')) {
                $parts = explode(' ', trim(substr($line, 12)));
                $ahead = (int) ltrim($parts[0], '+');
                $behind = (int) ltrim($parts[1], '-');
            } elseif (str_starts_with($line, '1 ')) {
                // Ordinary changed entry: 1 <XY> <sub> <mH> <mI> <mW> <hH> <hI> <path>
                $parts = preg_split('/\s+/', $line, 9);
                $changedFiles->push(new ChangedFile(
                    path: $parts[8] ?? '',
                    oldPath: null,
                    indexStatus: $parts[1][0] ?? '.',
                    worktreeStatus: $parts[1][1] ?? '.',
                ));
            } elseif (str_starts_with($line, '2 ')) {
                // Renamed/copied entry: 2 <XY> <sub> <mH> <mI> <mW> <hH> <hI> <X><score> <path><sep><origPath>
                $parts = preg_split('/\s+/', $line, 10);
                $paths = explode("\t", $parts[9] ?? '');
                $changedFiles->push(new ChangedFile(
                    path: $paths[1] ?? '',
                    oldPath: $paths[0] ?? null,
                    indexStatus: $parts[1][0] ?? '.',
                    worktreeStatus: $parts[1][1] ?? '.',
                ));
            } elseif (str_starts_with($line, 'u ')) {
                // Unmerged entry: u <XY> <sub> <m1> <m2> <m3> <mW> <h1> <h2> <h3> <path>
                $parts = preg_split('/\s+/', $line, 11);
                $changedFiles->push(new ChangedFile(
                    path: $parts[10] ?? '',
                    oldPath: null,
                    indexStatus: $parts[1][0] ?? 'U',
                    worktreeStatus: $parts[1][1] ?? 'U',
                ));
            } elseif (str_starts_with($line, '? ')) {
                // Untracked file
                $changedFiles->push(new ChangedFile(
                    path: trim(substr($line, 2)),
                    oldPath: null,
                    indexStatus: '?',
                    worktreeStatus: '?',
                ));
            } elseif (str_starts_with($line, '! ')) {
                // Ignored file
                $changedFiles->push(new ChangedFile(
                    path: trim(substr($line, 2)),
                    oldPath: null,
                    indexStatus: '!',
                    worktreeStatus: '!',
                ));
            }
        }

        return new self($branch, $upstream, new AheadBehind($ahead, $behind), $changedFiles);
    }
}
