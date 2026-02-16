<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BranchService;
use App\Services\Git\GitCacheService;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use App\Services\Git\RemoteService;
use App\Services\Git\StashService;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\On;
use Livewire\Component;

class RepoSidebar extends Component
{
    public string $repoPath;

    public array $branches = [];

    public array $remotes = [];

    public array $tags = [];

    public array $stashes = [];

    public string $currentBranch = '';

    public bool $showAutoStashModal = false;

    public string $autoStashTargetBranch = '';

    private ?string $lastSidebarHash = null;

    public function mount(): void
    {
        $this->refreshSidebar();
    }

    public function refreshSidebar(): void
    {
        $gitService = new GitService($this->repoPath);
        $branchService = new BranchService($this->repoPath);
        $remoteService = new RemoteService($this->repoPath);
        $stashService = new StashService($this->repoPath);

        $status = $gitService->status();

        $branches = $branchService->branches()
            ->filter(fn ($branch) => ! $branch->isRemote)
            ->map(fn ($branch) => [
                'name' => $branch->name,
                'isCurrent' => $branch->isCurrent,
                'lastCommitSha' => $branch->lastCommitSha,
            ])
            ->toArray();

        $remotes = $remoteService->remotes()
            ->map(fn ($remote) => [
                'name' => $remote->name,
                'fetchUrl' => $remote->fetchUrl,
                'pushUrl' => $remote->pushUrl,
            ])
            ->toArray();

        $tags = $this->fetchTags();

        $stashes = $stashService->stashList()
            ->map(fn ($stash) => [
                'index' => $stash->index,
                'message' => $stash->message,
                'branch' => $stash->branch,
                'sha' => $stash->sha,
            ])
            ->toArray();

        // Hash check
        $sidebarHash = md5(
            serialize($branches).
            serialize($remotes).
            serialize($tags).
            serialize($stashes).
            $status->branch
        );
        if ($this->lastSidebarHash === $sidebarHash) {
            return;
        }
        $this->lastSidebarHash = $sidebarHash;

        // Assign to public properties
        $this->currentBranch = $status->branch;
        $this->branches = $branches;
        $this->remotes = $remotes;
        $this->tags = $tags;
        $this->stashes = $stashes;
    }

    public function switchBranch(string $name): void
    {
        try {
            $branchService = new BranchService($this->repoPath);
            $branchService->switchBranch($name);
            $this->refreshSidebar();
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            if (GitErrorHandler::isDirtyTreeError($e->getMessage())) {
                $this->autoStashTargetBranch = $name;
                $this->showAutoStashModal = true;
            } else {
                $this->dispatch('show-error', message: GitErrorHandler::translate($e->getMessage()), type: 'error', persistent: false);
            }
        }
    }

    public function applyStash(int $index): void
    {
        try {
            $stashService = new StashService($this->repoPath);
            $stashService->stashApply($index);
            $this->refreshSidebar();
            $this->dispatch('status-updated');
            $this->dispatch('refresh-staging');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: GitErrorHandler::translate($e->getMessage()), type: 'error', persistent: false);
        }
    }

    public function popStash(int $index): void
    {
        try {
            $stashService = new StashService($this->repoPath);
            $stashService->stashPop($index);
            $this->refreshSidebar();
            $this->dispatch('status-updated');
            $this->dispatch('refresh-staging');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: GitErrorHandler::translate($e->getMessage()), type: 'error', persistent: false);
        }
    }

    public function dropStash(int $index): void
    {
        try {
            $stashService = new StashService($this->repoPath);
            $stashService->stashDrop($index);
            $this->refreshSidebar();
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: GitErrorHandler::translate($e->getMessage()), type: 'error', persistent: false);
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
            $applyResult = Process::path($this->repoPath)->run('git stash apply stash@{0}');

            if ($applyResult->exitCode() === 0) {
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

            $this->refreshSidebar();
            $this->dispatch('status-updated');
            $this->dispatch('refresh-staging');
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

    #[On('stash-created')]
    public function handleStashCreated(): void
    {
        $this->refreshSidebar();
    }

    private function fetchTags(): array
    {
        $cache = new GitCacheService;

        return $cache->get($this->repoPath, 'tags', function () {
            $result = Process::path($this->repoPath)->run('git tag -l --format=%(refname:short) %(objectname:short)');

            if ($result->exitCode() !== 0) {
                return [];
            }

            $lines = array_filter(explode("\n", trim($result->output())));

            return collect($lines)->map(function ($line) {
                $parts = preg_split('/\s+/', trim($line), 2);

                return [
                    'name' => $parts[0] ?? '',
                    'sha' => $parts[1] ?? '',
                ];
            })->toArray();
        }, 60);
    }

    public function render()
    {
        return view('livewire.repo-sidebar');
    }
}
