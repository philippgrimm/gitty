<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BranchService;
use App\Services\Git\GitErrorHandler;
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
            }
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function render()
    {
        return view('livewire.branch-manager');
    }
}
