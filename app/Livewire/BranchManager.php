<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BranchService;
use App\Services\Git\GitService;
use Illuminate\Support\Collection;
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

    public function mount(): void
    {
        $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        $this->refreshBranches();
    }

    public function refreshBranches(): void
    {
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
    }

    public function switchBranch(string $name): void
    {
        $this->error = '';
        
        $branchService = new BranchService($this->repoPath);
        $branchService->switchBranch($name);
        
        $this->refreshBranches();
        $this->dispatch('status-updated');
    }

    public function createBranch(): void
    {
        $this->error = '';
        
        $branchService = new BranchService($this->repoPath);
        $branchService->createBranch($this->newBranchName, $this->baseBranch);
        
        $this->showCreateModal = false;
        $this->newBranchName = '';
        $this->refreshBranches();
        $this->dispatch('status-updated');
    }

    public function deleteBranch(string $name): void
    {
        $this->error = '';
        
        if ($name === $this->currentBranch) {
            $this->error = 'Cannot delete the current branch';
            return;
        }
        
        $branchService = new BranchService($this->repoPath);
        $branchService->deleteBranch($name, false);
        
        $this->refreshBranches();
        $this->dispatch('status-updated');
    }

    public function mergeBranch(string $name): void
    {
        $this->error = '';
        
        $branchService = new BranchService($this->repoPath);
        $mergeResult = $branchService->mergeBranch($name);
        
        if ($mergeResult->hasConflicts) {
            $conflictList = implode(', ', $mergeResult->conflictFiles);
            $this->error = "Merge conflicts detected in: {$conflictList}";
        }
        
        $this->refreshBranches();
        $this->dispatch('status-updated');
    }

    public function render()
    {
        return view('livewire.branch-manager');
    }
}
