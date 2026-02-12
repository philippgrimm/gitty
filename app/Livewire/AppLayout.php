<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class AppLayout extends Component
{
    public string $repoPath = '';

    public bool $sidebarCollapsed = false;

    public function mount(?string $repoPath = null): void
    {
        $this->repoPath = $repoPath ?? '';
        
        if (!empty($this->repoPath) && !is_dir($this->repoPath . '/.git')) {
            $this->repoPath = '';
        }
    }

    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = !$this->sidebarCollapsed;
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.app-layout');
    }
}
