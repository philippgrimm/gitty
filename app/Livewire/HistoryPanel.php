<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\CommitService;
use App\Services\Git\GitService;
use App\Services\Git\GraphService;
use App\Services\Git\ResetService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class HistoryPanel extends Component
{
    public string $repoPath;

    public int $commitsCount = 0;

    public int $page = 1;

    public int $perPage = 100;

    public bool $hasMore = false;

    public ?string $selectedCommitSha = null;

    public bool $showGraph = true;

    public bool $showResetModal = false;

    public bool $showRevertModal = false;

    public bool $showCherryPickModal = false;

    public ?string $resetTargetSha = null;

    public ?string $resetTargetMessage = null;

    public ?string $cherryPickTargetSha = null;

    public ?string $cherryPickTargetMessage = null;

    public string $resetMode = 'soft';

    public string $hardResetConfirmText = '';

    public bool $targetCommitPushed = false;

    public int $rebaseCommitCount = 5;

    private ?Collection $commits = null;

    public function mount(string $repoPath): void
    {
        $this->repoPath = $repoPath;
        $this->commits = collect();
        $this->loadCommits();
    }

    private function getCommits(): Collection
    {
        if ($this->commits === null) {
            $this->commits = collect();
        }

        return $this->commits;
    }

    private function loadCommits(): void
    {
        try {
            $gitService = new GitService($this->repoPath);
            $newCommits = $gitService->log($this->perPage, detailed: true);

            if ($this->page === 1) {
                $this->commits = $newCommits;
            } else {
                $this->commits = $this->getCommits()->concat($newCommits);
            }

            $this->hasMore = $this->checkHasMoreCommits($gitService);
            $this->commitsCount = $this->getCommits()->count();
        } catch (\Exception $e) {
            $this->commits = collect();
            $this->hasMore = false;
            $this->commitsCount = 0;
        }
    }

    private function checkHasMoreCommits(GitService $gitService): bool
    {
        $checkMore = $gitService->log($this->perPage + 1, detailed: true);

        return $checkMore->count() > $this->perPage;
    }

    public function loadMore(): void
    {
        $this->page++;
        $this->loadCommits();
    }

    public function selectCommit(string $sha): void
    {
        $this->selectedCommitSha = $sha;
        $this->dispatch('commit-selected', sha: $sha);
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
        $this->page = 1;
        $this->commits = collect();
        $this->selectedCommitSha = null;
        $this->loadCommits();
    }

    #[On('status-updated')]
    public function handleStatusUpdated(): void
    {
        $this->refreshCommitList();
    }

    private function refreshCommitList(): void
    {
        $this->page = 1;
        $this->selectedCommitSha = null;
        $this->loadCommits();
    }

    public function promptReset(string $sha, string $message): void
    {
        $this->resetTargetSha = $sha;
        $this->resetTargetMessage = $message;
        $this->resetMode = 'soft';
        $this->hardResetConfirmText = '';
        $this->targetCommitPushed = $this->isCommitPushed($sha);
        $this->showResetModal = true;
    }

    public function confirmReset(): void
    {
        if ($this->resetMode === 'hard' && $this->hardResetConfirmText !== 'DISCARD') {
            $this->dispatch('show-error', message: 'Type "DISCARD" to confirm hard reset');

            return;
        }

        try {
            $resetService = new ResetService($this->repoPath);

            match ($this->resetMode) {
                'soft' => $resetService->resetSoft($this->resetTargetSha),
                'mixed' => $resetService->resetMixed($this->resetTargetSha),
                'hard' => $resetService->resetHard($this->resetTargetSha),
            };

            $this->showResetModal = false;
            $this->dispatch('status-updated');
            $this->dispatch('refresh-staging');
            $this->dispatch('show-success', message: 'Reset to commit '.substr($this->resetTargetSha, 0, 8));
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    public function promptRevert(string $sha, string $message): void
    {
        $this->resetTargetSha = $sha;
        $this->resetTargetMessage = $message;
        $this->showRevertModal = true;
    }

    public function confirmRevert(): void
    {
        try {
            $resetService = new ResetService($this->repoPath);
            $resetService->revertCommit($this->resetTargetSha);

            $this->showRevertModal = false;
            $this->dispatch('status-updated');
            $this->dispatch('show-success', message: 'Reverted commit '.substr($this->resetTargetSha, 0, 8));
        } catch (\Exception $e) {
            $this->showRevertModal = false;
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    private function isCommitPushed(string $sha): bool
    {
        try {
            $gitService = new GitService($this->repoPath);
            $status = $gitService->status();

            if (empty($status->upstream)) {
                return false;
            }

            // Check if commit exists in remote
            $branchService = new \App\Services\Git\BranchService($this->repoPath);

            return $branchService->isCommitOnRemote($sha);
        } catch (\Exception) {
            return false;
        }
    }

    public function promptCherryPick(string $sha, string $message): void
    {
        $this->cherryPickTargetSha = $sha;
        $this->cherryPickTargetMessage = $message;
        $this->showCherryPickModal = true;
    }

    public function confirmCherryPick(): void
    {
        try {
            $commitService = new CommitService($this->repoPath);
            $result = $commitService->cherryPick($this->cherryPickTargetSha);

            if ($result->hasConflicts) {
                $this->showCherryPickModal = false;
                $this->dispatch('show-error', message: 'Cherry-pick failed: conflicts detected. Resolve conflicts and continue or abort.');

                return;
            }

            $this->showCherryPickModal = false;
            $this->dispatch('status-updated');
            $this->dispatch('show-success', message: 'Cherry-picked commit '.substr($this->cherryPickTargetSha, 0, 8));
        } catch (\Exception $e) {
            $this->showCherryPickModal = false;
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    public function promptInteractiveRebase(string $sha): void
    {
        $this->dispatch('open-rebase-modal', ontoCommit: $sha, count: $this->rebaseCommitCount);
    }

    public function render()
    {
        $graphData = [];
        if ($this->showGraph) {
            try {
                $graphService = new GraphService($this->repoPath);
                $graphData = $graphService->getGraphData($this->perPage);
            } catch (\Exception $e) {
                $graphData = [];
            }
        }

        return view('livewire.history-panel', [
            'commits' => $this->getCommits(),
            'graphData' => $graphData,
        ]);
    }
}
