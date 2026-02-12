<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\CommitService;
use App\Services\Git\GitService;
use Livewire\Attributes\On;
use Livewire\Component;

class CommitPanel extends Component
{
    public string $repoPath;

    public string $message = '';

    public bool $isAmend = false;

    public int $stagedCount = 0;

    public ?string $lastCommitMessage = null;

    public ?string $error = null;

    public function mount(): void
    {
        $this->refreshStagedCount();
    }

    #[On('status-updated')]
    public function refreshStagedCount(): void
    {
        $gitService = new GitService($this->repoPath);
        $status = $gitService->status();

        $this->stagedCount = $status->changedFiles
            ->filter(fn ($file) => $file['indexStatus'] !== '.' && $file['indexStatus'] !== '?')
            ->count();
    }

    public function commit(): void
    {
        if (empty(trim($this->message))) {
            return;
        }

        $this->error = null;

        try {
            $commitService = new CommitService($this->repoPath);

            if ($this->isAmend) {
                $commitService->commitAmend($this->message);
            } else {
                $commitService->commit($this->message);
            }

            $this->message = '';
            $this->isAmend = false;
            $this->dispatch('committed');
        } catch (\Exception $e) {
            $this->error = 'Commit failed';
        }
    }

    public function commitAndPush(): void
    {
        if (empty(trim($this->message))) {
            return;
        }

        $this->error = null;

        try {
            $commitService = new CommitService($this->repoPath);
            $commitService->commitAndPush($this->message);

            $this->message = '';
            $this->isAmend = false;
            $this->dispatch('committed');
        } catch (\Exception $e) {
            $this->error = 'Commit and push failed';
        }
    }

    public function toggleAmend(): void
    {
        $this->isAmend = ! $this->isAmend;

        if ($this->isAmend) {
            $commitService = new CommitService($this->repoPath);
            $this->message = $commitService->lastCommitMessage();
        } else {
            $this->message = '';
        }
    }

    public function render()
    {
        return view('livewire.commit-panel');
    }
}
