<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BranchService;
use App\Services\Git\GitCacheService;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use App\Services\Git\StashService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class BranchManager extends Component
{
    public string $repoPath;

    public string $currentBranch;

    public array $branches = [];

    public array $aheadBehind;

    public bool $isDetachedHead = false;

    public string $error = '';

    public string $branchQuery = '';

    public bool $showAutoStashModal = false;

    public string $autoStashTargetBranch = '';

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
            $this->aheadBehind = ['ahead' => $status->aheadBehind->ahead, 'behind' => $status->aheadBehind->behind];
            $this->isDetachedHead = $gitService->isDetachedHead();

            $this->branches = $branchService->branches()
                ->map(fn ($branch) => [
                    'name' => $branch->name,
                    'isRemote' => $branch->isRemote,
                    'isCurrent' => $branch->isCurrent,
                    'lastCommitSha' => $branch->lastCommitSha,
                ])
                ->toArray();

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
            if (GitErrorHandler::isDirtyTreeError($e->getMessage())) {
                $this->autoStashTargetBranch = $name;
                $this->showAutoStashModal = true;
            } else {
                $this->error = GitErrorHandler::translate($e->getMessage());
                $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
            }
        }
    }

    #[On('palette-create-branch')]
    public function handlePaletteCreateBranch(string $name): void
    {
        try {
            $branchService = new BranchService($this->repoPath);
            $branchService->createBranch($name, $this->currentBranch);

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

    public function confirmAutoStash(): void
    {
        $this->showAutoStashModal = false;

        try {
            // Create stash with untracked files
            $stashService = new StashService($this->repoPath);
            $stashService->stash("Auto-stash: switching to {$this->autoStashTargetBranch}", true);

            // Switch branch
            $branchService = new BranchService($this->repoPath);
            $branchService->switchBranch($this->autoStashTargetBranch);

            // Try to restore stashed changes
            $applySucceeded = $stashService->tryStashApply(0);

            if ($applySucceeded) {
                // Success - drop the stash
                $stashService->stashDrop(0);
                $this->dispatch('show-error', message: "Switched to {$this->autoStashTargetBranch} (changes restored)", type: 'success', persistent: false);
            } else {
                // Conflict - preserve stash
                $this->dispatch('show-error', message: "Switched to {$this->autoStashTargetBranch}. Some stashed changes conflicted — stash preserved in stash list.", type: 'warning', persistent: true);
            }

            // Invalidate cache
            $cache = new GitCacheService;
            $cache->invalidateGroup($this->repoPath, 'branches');
            $cache->invalidateGroup($this->repoPath, 'status');
            $cache->invalidateGroup($this->repoPath, 'stashes');

            $this->refreshBranches();
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: GitErrorHandler::translate($e->getMessage()), type: 'error', persistent: false);
        } finally {
            $this->autoStashTargetBranch = '';
        }
    }

    public function cancelAutoStash(): void
    {
        $this->showAutoStashModal = false;
        $this->autoStashTargetBranch = '';
    }

    /**
     * @return Collection<int, array>
     */
    public function getFilteredLocalBranchesProperty(): Collection
    {
        return $this->filterByQuery(
            collect($this->branches)->filter(fn ($branch) => ! $branch['isRemote'] && ! str_contains($branch['name'], 'remotes/'))
        )->sortBy([
            fn ($a, $b) => $b['isCurrent'] <=> $a['isCurrent'],
            fn ($a, $b) => $a['name'] <=> $b['name'],
        ]);
    }

    /**
     * @return Collection<int, array>
     */
    public function getFilteredRemoteBranchesProperty(): Collection
    {
        $localBranchNames = collect($this->branches)
            ->filter(fn ($branch) => ! $branch['isRemote'] && ! str_contains($branch['name'], 'remotes/'))
            ->pluck('name');

        $remote = collect($this->branches)
            ->filter(fn ($branch) => $branch['isRemote'] || str_contains($branch['name'], 'remotes/'))
            ->filter(function ($branch) use ($localBranchNames) {
                // Strip remote prefix (e.g., "origin/main" -> "main", "remotes/origin/feature/xyz" -> "feature/xyz")
                $cleanName = preg_replace('/^(remotes\/)?[^\/]+\//', '', $branch['name']);

                return ! $localBranchNames->contains($cleanName);
            });

        return $this->filterByQuery($remote);
    }

    /**
     * @param  Collection<int, array>  $branches
     * @return Collection<int, array>
     */
    private function filterByQuery(Collection $branches): Collection
    {
        if (empty($this->branchQuery)) {
            return $branches;
        }

        $query = strtolower($this->branchQuery);

        return $branches->filter(fn ($branch) => str_contains(strtolower($branch['name']), $query));
    }

    public function render()
    {
        return view('livewire.branch-manager');
    }
}
