<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class GraphNode
{
    /**
     * @param  array<string>  $parents
     * @param  array<string>  $refs
     */
    public function __construct(
        public string $sha,
        public array $parents,
        public string $branch,
        public array $refs,
        public string $message,
        public string $author,
        public string $date,
        public int $lane,
    ) {}
}
