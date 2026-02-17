<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BranchService;
use App\Services\Git\GitCacheService;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use App\Services\Git\RemoteService;
use App\Services\Git\StashService;
use App\Services\Git\TagService;
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

    public bool $showCreateTagModal = false;

    public string $newTagName = '';

    public ?string $newTagMessage = null;

    public ?string $newTagCommit = null;

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

        $tagService = new TagService($this->repoPath);
        $tags = $tagService->tags()->toArray();

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

    #[On('status-updated')]
    public function handleStatusUpdated(): void
    {
        $this->refreshSidebar();
    }

    #[On('stash-created')]
    public function handleStashCreated(): void
    {
        $this->refreshSidebar();
    }

    public function createTag(): void
    {
        if (empty(trim($this->newTagName))) {
            return;
        }

        try {
            $tagService = new TagService($this->repoPath);
            $tagService->createTag(
                trim($this->newTagName),
                $this->newTagMessage ? trim($this->newTagMessage) : null,
                $this->newTagCommit ? trim($this->newTagCommit) : null
            );
            $this->showCreateTagModal = false;
            $this->newTagName = '';
            $this->newTagMessage = null;
            $this->newTagCommit = null;
            $this->refreshSidebar();
            $this->dispatch('show-error', message: 'Tag created successfully', type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage(), type: 'error');
        }
    }

    public function deleteTag(string $name): void
    {
        try {
            $tagService = new TagService($this->repoPath);
            $tagService->deleteTag($name);
            $this->refreshSidebar();
            $this->dispatch('show-error', message: "Tag '{$name}' deleted", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage(), type: 'error');
        }
    }

    public function pushTag(string $name): void
    {
        try {
            $tagService = new TagService($this->repoPath);
            $tagService->pushTag($name);
            $this->dispatch('show-error', message: "Tag '{$name}' pushed to remote", type: 'success');
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage(), type: 'error');
        }
    }

    #[On('palette-create-tag')]
    public function handlePaletteCreateTag(): void
    {
        $this->showCreateTagModal = true;
    }

    public function render()
    {
        return view('livewire.repo-sidebar');
    }
}
