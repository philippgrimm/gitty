<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class Commit
{
    public function __construct(
        public string $sha,
        public string $shortSha,
        public string $message,
        public string $author,
        public string $email,
        public string $date,
        public array $refs,
    ) {}

    public static function fromLogLine(string $line): self
    {
        // Parse format: <sha> <message>
        $parts = explode(' ', $line, 2);
        $sha = $parts[0] ?? '';
        $message = $parts[1] ?? '';

        return new self(
            sha: $sha,
            shortSha: substr($sha, 0, 7),
            message: $message,
            author: '',
            email: '',
            date: '',
            refs: [],
        );
    }

    public static function fromDetailedOutput(string $output): self
    {
        $lines = explode("\n", $output);
        $sha = '';
        $author = '';
        $email = '';
        $date = '';
        $message = '';
        $refs = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'commit ')) {
                $commitLine = trim(substr($line, 7));
                $parts = explode(' ', $commitLine);
                $sha = $parts[0];
                // Parse refs if present (e.g., "commit abc123 (HEAD -> main, origin/main)")
                if (count($parts) > 1) {
                    $refString = implode(' ', array_slice($parts, 1));
                    $refString = trim($refString, '()');
                    $refs = array_map('trim', explode(',', $refString));
                }
            } elseif (str_starts_with($line, 'Author: ')) {
                $authorLine = trim(substr($line, 8));
                if (preg_match('/^(.+?)\s+<(.+?)>$/', $authorLine, $matches)) {
                    $author = $matches[1];
                    $email = $matches[2];
                }
            } elseif (str_starts_with($line, 'Date: ')) {
                $date = trim(substr($line, 6));
            } elseif (trim($line) !== '' && ! str_starts_with($line, ' ') && $sha !== '') {
                // Start of diff section, stop parsing
                break;
            } elseif (str_starts_with($line, '    ') && $sha !== '') {
                // Message line (indented with 4 spaces)
                $message .= trim(substr($line, 4))."\n";
            }
        }

        return new self(
            sha: $sha,
            shortSha: substr($sha, 0, 7),
            message: trim($message),
            author: $author,
            email: $email,
            date: $date,
            refs: $refs,
        );
    }
}
