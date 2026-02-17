<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class BlameLine
{
    public function __construct(
        public string $commitSha,
        public string $author,
        public string $date,
        public int $lineNumber,
        public string $content,
    ) {}
}
