<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

readonly class GitStatus
{
    public function __construct(
        public string $branch,
        public ?string $upstream,
        public array $aheadBehind,
        public Collection $changedFiles,
    ) {}

    public static function fromOutput(string $output): self
    {
        $lines = explode("\n", trim($output));
        $branch = '';
        $upstream = null;
        $aheadBehind = ['ahead' => 0, 'behind' => 0];
        $changedFiles = collect();

        foreach ($lines as $line) {
            if (str_starts_with($line, '# branch.head ')) {
                $branch = trim(substr($line, 14));
            } elseif (str_starts_with($line, '# branch.upstream ')) {
                $upstream = trim(substr($line, 18));
            } elseif (str_starts_with($line, '# branch.ab ')) {
                $parts = explode(' ', trim(substr($line, 12)));
                $aheadBehind = [
                    'ahead' => (int) ltrim($parts[0], '+'),
                    'behind' => (int) ltrim($parts[1], '-'),
                ];
            } elseif (str_starts_with($line, '1 ')) {
                // Ordinary changed entry: 1 <XY> <sub> <mH> <mI> <mW> <hH> <hI> <path>
                $parts = preg_split('/\s+/', $line, 9);
                $changedFiles->push([
                    'indexStatus' => $parts[1][0] ?? '.',
                    'worktreeStatus' => $parts[1][1] ?? '.',
                    'path' => $parts[8] ?? '',
                    'oldPath' => null,
                ]);
            } elseif (str_starts_with($line, '2 ')) {
                // Renamed/copied entry: 2 <XY> <sub> <mH> <mI> <mW> <hH> <hI> <X><score> <path><sep><origPath>
                $parts = preg_split('/\s+/', $line, 10);
                $paths = explode("\t", $parts[9] ?? '');
                $changedFiles->push([
                    'indexStatus' => $parts[1][0] ?? '.',
                    'worktreeStatus' => $parts[1][1] ?? '.',
                    'path' => $paths[1] ?? '',
                    'oldPath' => $paths[0] ?? null,
                ]);
            } elseif (str_starts_with($line, 'u ')) {
                // Unmerged entry: u <XY> <sub> <m1> <m2> <m3> <mW> <h1> <h2> <h3> <path>
                $parts = preg_split('/\s+/', $line, 11);
                $changedFiles->push([
                    'indexStatus' => $parts[1][0] ?? 'U',
                    'worktreeStatus' => $parts[1][1] ?? 'U',
                    'path' => $parts[10] ?? '',
                    'oldPath' => null,
                ]);
            } elseif (str_starts_with($line, '? ')) {
                // Untracked file
                $changedFiles->push([
                    'indexStatus' => '?',
                    'worktreeStatus' => '?',
                    'path' => trim(substr($line, 2)),
                    'oldPath' => null,
                ]);
            } elseif (str_starts_with($line, '! ')) {
                // Ignored file
                $changedFiles->push([
                    'indexStatus' => '!',
                    'worktreeStatus' => '!',
                    'path' => trim(substr($line, 2)),
                    'oldPath' => null,
                ]);
            }
        }

        return new self($branch, $upstream, $aheadBehind, $changedFiles);
    }
}
