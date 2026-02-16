<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\CommitService;
use App\Services\Git\GitErrorHandler;
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

    public string $currentPrefill = '';

    /** @var array<int, string> */
    public array $commitHistory = [];

    public ?string $error = null;

    public function mount(): void
    {
        $gitService = new GitService($this->repoPath);
        $status = $gitService->status();
        $this->stagedCount = $status->changedFiles
            ->filter(fn ($file) => $file['indexStatus'] !== '.' && $file['indexStatus'] !== '?')
            ->count();

        $this->currentPrefill = $this->getCommitPrefill();
        $this->message = $this->currentPrefill;
        $this->loadCommitHistory();
    }

    public function loadCommitHistory(): void
    {
        try {
            $gitService = new GitService($this->repoPath);
            $this->commitHistory = $gitService->log(10)
                ->pluck('message')
                ->map(fn (string $message) => \Illuminate\Support\Str::before($message, "\n"))
                ->values()
                ->toArray();
        } catch (\Exception) {
            $this->commitHistory = [];
        }
    }

    #[On('status-updated')]
    public function refreshStagedCount(int $stagedCount = 0, array $aheadBehind = []): void
    {
        $this->stagedCount = $stagedCount;

        $newPrefill = $this->getCommitPrefill();
        if ($this->message === '' || $this->message === $this->currentPrefill) {
            $this->currentPrefill = $newPrefill;
            $this->message = $newPrefill;
        } else {
            $this->currentPrefill = $newPrefill;
        }

        $this->loadCommitHistory();
    }

    #[On('keyboard-commit')]
    public function handleKeyboardCommit(): void
    {
        $this->commit();
    }

    #[On('keyboard-commit-push')]
    public function handleKeyboardCommitPush(): void
    {
        $this->commitAndPush();
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

            $this->isAmend = false;
            $this->currentPrefill = $this->getCommitPrefill();
            $this->message = $this->currentPrefill;
            $this->loadCommitHistory();
            $this->dispatch('committed');
            $this->dispatch('prefill-updated');
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
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

            $this->isAmend = false;
            $this->currentPrefill = $this->getCommitPrefill();
            $this->message = $this->currentPrefill;
            $this->loadCommitHistory();
            $this->dispatch('committed');
            $this->dispatch('prefill-updated');
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function toggleAmend(): void
    {
        $this->isAmend = ! $this->isAmend;

        if ($this->isAmend) {
            $commitService = new CommitService($this->repoPath);
            $this->message = $commitService->lastCommitMessage();
        } else {
            $this->currentPrefill = $this->getCommitPrefill();
            $this->message = $this->currentPrefill;
        }
    }

    public function getCommitPrefill(): string
    {
        try {
            $gitService = new GitService($this->repoPath);
            $branch = $gitService->currentBranch();

            if (preg_match('/^(feature|bugfix)\/([A-Z]+-\d+)/', $branch, $matches)) {
                $type = $matches[1] === 'feature' ? 'feat' : 'fix';
                $ticket = $matches[2];

                return "{$type}({$ticket}): ";
            }
        } catch (\Exception) {
            // Graceful fallback for detached HEAD, empty repo, etc.
        }

        return '';
    }

    #[On('palette-toggle-amend')]
    public function handlePaletteToggleAmend(): void
    {
        $this->toggleAmend();
    }

    public function render()
    {
        return view('livewire.commit-panel');
    }
}
