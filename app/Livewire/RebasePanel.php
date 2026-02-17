<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\RebaseService;
use Livewire\Attributes\On;
use Livewire\Component;

class RebasePanel extends Component
{
    public string $repoPath;

    public bool $showRebaseModal = false;

    public string $ontoCommit = '';

    public int $commitCount = 5;

    public array $rebasePlan = [];

    public bool $isRebasing = false;

    public bool $showForceWarning = false;

    public function mount(string $repoPath): void
    {
        $this->repoPath = $repoPath;
        $this->checkRebaseState();
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
        $this->checkRebaseState();
    }

    #[On('status-updated')]
    public function handleStatusUpdated(): void
    {
        $this->checkRebaseState();
    }

    #[On('open-rebase-modal')]
    public function openRebaseModal(string $ontoCommit, int $count = 5): void
    {
        $this->ontoCommit = $ontoCommit;
        $this->commitCount = $count;
        $this->showForceWarning = $this->checkIfCommitsPushed();

        try {
            $rebaseService = new RebaseService($this->repoPath);
            $commits = $rebaseService->getRebaseCommits($this->ontoCommit, $this->commitCount);

            $this->rebasePlan = $commits->map(function (array $commit) {
                return [
                    'sha' => $commit['sha'],
                    'shortSha' => $commit['shortSha'],
                    'message' => $commit['message'],
                    'action' => 'pick',
                ];
            })->toArray();

            $this->showRebaseModal = true;
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    public function updateAction(int $index, string $action): void
    {
        if (isset($this->rebasePlan[$index])) {
            $this->rebasePlan[$index]['action'] = $action;
        }
    }

    public function reorderCommits(array $newOrder): void
    {
        $reordered = [];
        foreach ($newOrder as $index) {
            if (isset($this->rebasePlan[$index])) {
                $reordered[] = $this->rebasePlan[$index];
            }
        }
        $this->rebasePlan = $reordered;
    }

    public function startRebase(): void
    {
        try {
            $rebaseService = new RebaseService($this->repoPath);
            $rebaseService->startRebase($this->ontoCommit, $this->rebasePlan);

            $this->showRebaseModal = false;
            $this->checkRebaseState();
            $this->dispatch('status-updated');
            $this->dispatch('show-success', message: 'Rebase started successfully');

            if ($this->showForceWarning) {
                $this->dispatch('show-warning', message: 'Rebase complete. Force push required to update remote.');
            }
        } catch (\Exception $e) {
            $this->showRebaseModal = false;
            $this->checkRebaseState();
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    #[On('palette-continue-rebase')]
    public function continueRebase(): void
    {
        try {
            $rebaseService = new RebaseService($this->repoPath);
            $rebaseService->continueRebase();

            $this->checkRebaseState();
            $this->dispatch('status-updated');
            $this->dispatch('show-success', message: 'Rebase continued');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    #[On('palette-abort-rebase')]
    public function abortRebase(): void
    {
        try {
            $rebaseService = new RebaseService($this->repoPath);
            $rebaseService->abortRebase();

            $this->checkRebaseState();
            $this->dispatch('status-updated');
            $this->dispatch('show-success', message: 'Rebase aborted');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    private function checkRebaseState(): void
    {
        try {
            $rebaseService = new RebaseService($this->repoPath);
            $this->isRebasing = $rebaseService->isRebasing();
        } catch (\Exception) {
            $this->isRebasing = false;
        }
    }

    private function checkIfCommitsPushed(): bool
    {
        try {
            $branchService = new \App\Services\Git\BranchService($this->repoPath);

            return $branchService->isCommitOnRemote($this->ontoCommit);
        } catch (\Exception) {
            return false;
        }
    }

    public function render()
    {
        return view('livewire.rebase-panel');
    }
}
