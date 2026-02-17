<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class AheadBehind
{
    public function __construct(
        public int $ahead,
        public int $behind,
    ) {}

    public function isUpToDate(): bool
    {
        return $this->ahead === 0 && $this->behind === 0;
    }

    public function hasDiverged(): bool
    {
        return $this->ahead > 0 && $this->behind > 0;
    }
}
