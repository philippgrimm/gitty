<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ConflictFile
{
    public function __construct(
        public string $path,
        public string $status,
        public string $oursContent,
        public string $theirsContent,
        public string $baseContent,
        public bool $isBinary = false,
    ) {}
}
