<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class Branch
{
    public function __construct(
        public string $name,
        public bool $isRemote,
        public bool $isCurrent,
        public ?string $upstream,
        public ?array $aheadBehind,
        public ?string $lastCommitSha,
    ) {}

    public static function fromBranchLine(string $line): self
    {
        $isCurrent = str_starts_with($line, '* ');
        $line = ltrim($line, '* ');
        $parts = preg_split('/\s+/', trim($line), 3);

        $name = $parts[0] ?? '';
        $isRemote = str_starts_with($name, 'remotes/');
        $lastCommitSha = $parts[1] ?? null;

        return new self(
            name: $name,
            isRemote: $isRemote,
            isCurrent: $isCurrent,
            upstream: null,
            aheadBehind: null,
            lastCommitSha: $lastCommitSha,
        );
    }
}
