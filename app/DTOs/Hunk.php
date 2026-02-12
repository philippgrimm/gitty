<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

readonly class Hunk
{
    public function __construct(
        public int $oldStart,
        public int $oldCount,
        public int $newStart,
        public int $newCount,
        public string $header,
        public Collection $lines,
    ) {}

    public static function fromRawLines(array $rawLines): Collection
    {
        $hunks = collect();
        $currentHunk = null;
        $oldLineNum = 0;
        $newLineNum = 0;

        foreach ($rawLines as $line) {
            if (str_starts_with($line, '@@ ')) {
                // Save previous hunk
                if ($currentHunk !== null) {
                    $hunks->push($currentHunk);
                }

                // Parse hunk header: @@ -old,count +new,count @@ context
                if (preg_match('/@@ -(\d+)(?:,(\d+))? \+(\d+)(?:,(\d+))? @@(.*)/', $line, $matches)) {
                    $oldStart = (int) $matches[1];
                    $oldCount = isset($matches[2]) && $matches[2] !== '' ? (int) $matches[2] : 1;
                    $newStart = (int) $matches[3];
                    $newCount = isset($matches[4]) && $matches[4] !== '' ? (int) $matches[4] : 1;
                    $header = trim($matches[5]);

                    $oldLineNum = $oldStart;
                    $newLineNum = $newStart;

                    $currentHunk = [
                        'oldStart' => $oldStart,
                        'oldCount' => $oldCount,
                        'newStart' => $newStart,
                        'newCount' => $newCount,
                        'header' => $header,
                        'lines' => collect(),
                    ];
                }
            } elseif ($currentHunk !== null) {
                // Parse hunk line
                $type = 'context';
                $content = $line;
                $oldLine = null;
                $newLine = null;

                if (str_starts_with($line, '+')) {
                    $type = 'addition';
                    $content = substr($line, 1);
                    $newLine = $newLineNum++;
                } elseif (str_starts_with($line, '-')) {
                    $type = 'deletion';
                    $content = substr($line, 1);
                    $oldLine = $oldLineNum++;
                } elseif (str_starts_with($line, ' ')) {
                    $type = 'context';
                    $content = substr($line, 1);
                    $oldLine = $oldLineNum++;
                    $newLine = $newLineNum++;
                }

                $currentHunk['lines']->push(
                    new HunkLine($type, $content, $oldLine, $newLine)
                );
            }
        }

        // Save last hunk
        if ($currentHunk !== null) {
            $hunks->push(
                new self(
                    $currentHunk['oldStart'],
                    $currentHunk['oldCount'],
                    $currentHunk['newStart'],
                    $currentHunk['newCount'],
                    $currentHunk['header'],
                    $currentHunk['lines']
                )
            );
        }

        return $hunks;
    }
}
