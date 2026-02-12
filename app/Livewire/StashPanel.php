<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\StashService;
use Livewire\Component;

class StashPanel extends Component
{
    public string $repoPath;

    public array $stashes = [];

    public string $stashMessage = '';

    public bool $includeUntracked = false;

    public bool $showCreateModal = false;

    public string $error = '';

    public function mount(): void
    {
        $this->refreshStashes();
    }

    public function refreshStashes(): void
    {
        $stashService = new StashService($this->repoPath);
        
        $this->stashes = $stashService->stashList()
            ->map(fn ($stash) => [
                'index' => $stash->index,
                'message' => $stash->message,
                'branch' => $stash->branch,
                'sha' => $stash->sha,
            ])
            ->toArray();
    }

    public function createStash(): void
    {
        $this->error = '';
        
        $stashService = new StashService($this->repoPath);
        $stashService->stash($this->stashMessage, $this->includeUntracked);
        
        $this->showCreateModal = false;
        $this->stashMessage = '';
        $this->includeUntracked = false;
        
        $this->refreshStashes();
        $this->dispatch('status-updated');
    }

    public function applyStash(int $index): void
    {
        $this->error = '';
        
        $stashService = new StashService($this->repoPath);
        $stashService->stashApply($index);
        
        $this->refreshStashes();
        $this->dispatch('status-updated');
    }

    public function popStash(int $index): void
    {
        $this->error = '';
        
        $stashService = new StashService($this->repoPath);
        $stashService->stashPop($index);
        
        $this->refreshStashes();
        $this->dispatch('status-updated');
    }

    public function dropStash(int $index): void
    {
        $this->error = '';
        
        $stashService = new StashService($this->repoPath);
        $stashService->stashDrop($index);
        
        $this->refreshStashes();
        $this->dispatch('status-updated');
    }

    public function render()
    {
        return view('livewire.stash-panel');
    }
}
