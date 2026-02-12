<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class MergeResult
{
    public function __construct(
        public bool $success,
        public bool $hasConflicts,
        public array $conflictFiles,
        public string $message,
    ) {}

    public static function fromMergeOutput(string $output, int $exitCode): self
    {
        $success = $exitCode === 0;
        $hasConflicts = str_contains($output, 'CONFLICT') || str_contains($output, 'Automatic merge failed');
        $conflictFiles = [];

        if ($hasConflicts) {
            // Extract conflict files from output
            $lines = explode("\n", $output);
            foreach ($lines as $line) {
                if (preg_match('/CONFLICT.*in (.+)$/', $line, $matches)) {
                    $conflictFiles[] = trim($matches[1]);
                }
            }
        }

        return new self(
            success: $success,
            hasConflicts: $hasConflicts,
            conflictFiles: $conflictFiles,
            message: trim($output),
        );
    }
}
