<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class HistoryRow
{
    /**
     * @param  array<string>  $parents
     * @param  array<string>  $refs
     * @param  array<string>  $graphCells
     * @param  array<array<string>>  $continuationCells
     */
    public function __construct(
        public string $sha,
        public string $shortSha,
        public array $parents,
        public array $refs,
        public string $message,
        public string $author,
        public string $date,
        public array $graphCells,
        public array $continuationCells,
        public bool $hasGraphData,
    ) {}
}
