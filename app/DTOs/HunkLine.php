<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class HunkLine
{
    public function __construct(
        public string $type,
        public string $content,
        public ?int $oldLineNumber,
        public ?int $newLineNumber,
    ) {}
}
