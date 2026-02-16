<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BranchService;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use Livewire\Component;

class BranchManager extends Component
{
    public string $repoPath;

    public string $currentBranch;

    public array $branches = [];

    public array $aheadBehind;

    public bool $showCreateModal = false;

    public string $newBranchName = '';

    public string $baseBranch = '';

    public bool $isDetachedHead = false;

    public string $error = '';

    public string $branchQuery = '';

    public function mount(): void
    {
        $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        $this->refreshBranches();
    }

    public function refreshBranches(): void
    {
        try {
            $gitService = new GitService($this->repoPath);
            $branchService = new BranchService($this->repoPath);

            $status = $gitService->status();
            $this->currentBranch = $status->branch;
            $this->aheadBehind = $status->aheadBehind;
            $this->isDetachedHead = $gitService->isDetachedHead();

            $this->branches = $branchService->branches()
                ->map(fn ($branch) => [
                    'name' => $branch->name,
                    'isRemote' => $branch->isRemote,
                    'isCurrent' => $branch->isCurrent,
                    'lastCommitSha' => $branch->lastCommitSha,
                ])
                ->toArray();

            if (empty($this->baseBranch)) {
                $this->baseBranch = $this->currentBranch;
            }

            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function switchBranch(string $name): void
    {
        $this->error = '';

        try {
            $branchService = new BranchService($this->repoPath);
            $branchService->switchBranch($name);

            $this->refreshBranches();
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function createBranch(): void
    {
        $this->error = '';

        try {
            $branchService = new BranchService($this->repoPath);
            $branchService->createBranch($this->newBranchName, $this->baseBranch);

            $this->showCreateModal = false;
            $this->newBranchName = '';
            $this->refreshBranches();
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function deleteBranch(string $name): void
    {
        $this->error = '';

        if ($name === $this->currentBranch) {
            $this->error = 'Cannot delete the current branch';
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);

            return;
        }

        try {
            $branchService = new BranchService($this->repoPath);
            $branchService->deleteBranch($name, false);

            $this->refreshBranches();
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function mergeBranch(string $name): void
    {
        $this->error = '';

        try {
            $branchService = new BranchService($this->repoPath);
            $mergeResult = $branchService->mergeBranch($name);

            $this->refreshBranches();
            $this->dispatch('status-updated');

            if ($mergeResult->hasConflicts) {
                $conflictList = implode(', ', $mergeResult->conflictFiles);
                $this->error = "Merge conflicts detected in: {$conflictList}";
                $this->dispatch('show-error', message: $this->error, type: 'warning', persistent: true);
            } else {
                $this->dispatch('show-error', message: "Merged {$name} into {$this->currentBranch}", type: 'success', persistent: false);
            }
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function getFilteredLocalBranchesProperty(): \Illuminate\Support\Collection
    {
        $local = collect($this->branches)->filter(fn ($b) => ! $b['isRemote'] && ! str_contains($b['name'], 'remotes/'));

        if (! empty($this->branchQuery)) {
            $local = $local->filter(fn ($b) => str_contains(strtolower($b['name']), strtolower($this->branchQuery)));
        }

        return $local->sortBy([
            fn ($a, $b) => $b['isCurrent'] <=> $a['isCurrent'], // Current branch first
            fn ($a, $b) => $a['name'] <=> $b['name'], // Then alphabetically
        ]);
    }

    /**
     * @return \Illuminate\Support\Collection<int, array>
     */
    public function getFilteredRemoteBranchesProperty(): \Illuminate\Support\Collection
    {
        // Get local branch names for comparison
        $localBranchNames = collect($this->branches)
            ->filter(fn ($b) => ! $b['isRemote'] && ! str_contains($b['name'], 'remotes/'))
            ->pluck('name')
            ->toArray();

        // Filter remote branches
        $remote = collect($this->branches)
            ->filter(fn ($b) => $b['isRemote'] || str_contains($b['name'], 'remotes/'))
            ->filter(function ($b) use ($localBranchNames) {
                // Strip remote prefix (e.g., "origin/main" -> "main", "remotes/origin/feature/xyz" -> "feature/xyz")
                $cleanName = preg_replace('/^(remotes\/)?[^\/]+\//', '', $b['name']);

                // Only show remote branches that don't have a corresponding local branch
                return ! in_array($cleanName, $localBranchNames);
            });

        if (! empty($this->branchQuery)) {
            $remote = $remote->filter(fn ($b) => str_contains(strtolower($b['name']), strtolower($this->branchQuery)));
        }

        return $remote;
    }

    public function render()
    {
        return view('livewire.branch-manager');
    }
}
