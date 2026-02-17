<?php

declare(strict_types=1);

namespace App\DTOs;

class ChangedFile implements \ArrayAccess
{
    public function __construct(
        public readonly string $path,
        public readonly ?string $oldPath,
        public readonly string $indexStatus,
        public readonly string $worktreeStatus,
    ) {}

    public function isStaged(): bool
    {
        return $this->indexStatus !== '.' && $this->indexStatus !== '?' && $this->indexStatus !== '!';
    }

    public function isUnstaged(): bool
    {
        return $this->worktreeStatus !== '.' && $this->worktreeStatus !== '?';
    }

    public function isUntracked(): bool
    {
        return $this->indexStatus === '?' && $this->worktreeStatus === '?';
    }

    public function isUnmerged(): bool
    {
        return $this->indexStatus === 'U' || $this->worktreeStatus === 'U';
    }

    public function statusLabel(): string
    {
        if ($this->isUntracked()) {
            return 'untracked';
        }

        if ($this->isUnmerged()) {
            return 'unmerged';
        }

        $status = $this->indexStatus !== '.' ? $this->indexStatus : $this->worktreeStatus;

        return match ($status) {
            'M' => 'modified',
            'A' => 'added',
            'D' => 'deleted',
            'R' => 'renamed',
            'C' => 'copied',
            default => 'unknown',
        };
    }

    public function offsetExists(mixed $offset): bool
    {
        return property_exists($this, $offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->{$offset};
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \LogicException('ChangedFile is immutable');
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \LogicException('ChangedFile is immutable');
    }
}
