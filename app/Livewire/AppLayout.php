<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\GitCacheService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class AppLayout extends Component
{
    public string $repoPath = '';

    public bool $sidebarCollapsed = false;

    private ?string $previousRepoPath = null;

    public function mount(?string $repoPath = null): void
    {
        $this->repoPath = $repoPath ?? '';
        
        if (!empty($this->repoPath) && !is_dir($this->repoPath . '/.git')) {
            $this->repoPath = '';
        }

        $this->previousRepoPath = $this->repoPath;
    }

    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        if ($this->previousRepoPath && $this->previousRepoPath !== $path) {
            $cache = new GitCacheService();
            $cache->invalidateAll($this->previousRepoPath);
        }

        $this->previousRepoPath = $path;
        $this->repoPath = $path;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.app-layout');
    }
}
