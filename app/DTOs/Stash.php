<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class Stash
{
    public function __construct(
        public int $index,
        public string $message,
        public string $branch,
        public string $sha,
    ) {}

    public static function fromStashLine(string $line): self
    {
        // Parse format: stash@{0}: WIP on main: a1b2c3d feat: add new feature
        // or: stash@{1}: On feature/new-ui: Temporary changes for testing
        if (! preg_match('/^stash@\{(\d+)\}:\s+(.+)$/', $line, $matches)) {
            throw new \InvalidArgumentException("Invalid stash line format: {$line}");
        }

        $index = (int) $matches[1];
        $rest = $matches[2];

        // Extract branch and message
        $branch = '';
        $sha = '';
        $message = $rest;

        if (preg_match('/^(?:WIP on|On)\s+(.+?):\s+([a-f0-9]+)\s+(.+)$/', $rest, $detailMatches)) {
            $branch = $detailMatches[1];
            $sha = $detailMatches[2];
            $message = $detailMatches[3];
        } elseif (preg_match('/^(?:WIP on|On)\s+(.+?):\s+(.+)$/', $rest, $detailMatches)) {
            $branch = $detailMatches[1];
            $message = $detailMatches[2];
        }

        return new self(
            index: $index,
            message: $message,
            branch: $branch,
            sha: $sha,
        );
    }
}
