<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

readonly class DiffResult
{
    public function __construct(
        public Collection $files,
    ) {}

    public static function fromDiffOutput(string $output): self
    {
        if (trim($output) === '') {
            return new self(collect());
        }

        $files = collect();
        $currentFile = null;
        $lines = explode("\n", $output);

        foreach ($lines as $line) {
            if (str_starts_with($line, 'diff --git ')) {
                // Save previous file if exists
                if ($currentFile !== null) {
                    $files->push($currentFile);
                }

                // Start new file
                $currentFile = [
                    'oldPath' => '',
                    'newPath' => '',
                    'status' => 'modified',
                    'isBinary' => false,
                    'hunks' => collect(),
                    'additions' => 0,
                    'deletions' => 0,
                    'rawLines' => [],
                ];
            } elseif (str_starts_with($line, '--- ')) {
                $path = trim(substr($line, 4));
                $currentFile['oldPath'] = $path === '/dev/null' ? '' : ltrim($path, 'a/');
            } elseif (str_starts_with($line, '+++ ')) {
                $path = trim(substr($line, 4));
                $currentFile['newPath'] = $path === '/dev/null' ? '' : ltrim($path, 'b/');

                // Determine status
                if ($currentFile['oldPath'] === '') {
                    $currentFile['status'] = 'added';
                } elseif ($currentFile['newPath'] === '') {
                    $currentFile['status'] = 'deleted';
                }
            } elseif (str_starts_with($line, 'Binary files ')) {
                $currentFile['isBinary'] = true;
            } elseif ($currentFile !== null) {
                $currentFile['rawLines'][] = $line;

                // Count additions/deletions
                if (str_starts_with($line, '+') && ! str_starts_with($line, '+++')) {
                    $currentFile['additions']++;
                } elseif (str_starts_with($line, '-') && ! str_starts_with($line, '---')) {
                    $currentFile['deletions']++;
                }
            }
        }

        // Save last file
        if ($currentFile !== null) {
            $files->push($currentFile);
        }

        // Convert to DiffFile objects
        $diffFiles = $files->map(fn ($file) => DiffFile::fromArray($file));

        return new self($diffFiles);
    }
}
