<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class ChangedFile
{
    public function __construct(
        public string $path,
        public ?string $oldPath,
        public string $indexStatus,
        public string $worktreeStatus,
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

        // Prefer index status for staged files, worktree status for unstaged
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
}
