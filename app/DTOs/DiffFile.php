<?php

declare(strict_types=1);

namespace App\DTOs;

use Illuminate\Support\Collection;

readonly class DiffFile
{
    public function __construct(
        public string $oldPath,
        public string $newPath,
        public string $status,
        public bool $isBinary,
        public Collection $hunks,
        public int $additions,
        public int $deletions,
    ) {}

    public static function fromArray(array $data): self
    {
        $hunks = collect();

        if (! $data['isBinary'] && ! empty($data['rawLines'])) {
            $hunks = Hunk::fromRawLines($data['rawLines']);
        }

        return new self(
            oldPath: $data['oldPath'],
            newPath: $data['newPath'],
            status: $data['status'],
            isBinary: $data['isBinary'],
            hunks: $hunks,
            additions: $data['additions'],
            deletions: $data['deletions'],
        );
    }

    public function getDisplayPath(): string
    {
        return $this->newPath ?: $this->oldPath;
    }
}
