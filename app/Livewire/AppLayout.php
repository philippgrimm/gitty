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
        if (! \App\Services\Git\GitConfigValidator::checkGitBinary()) {
            $this->dispatch('show-error', message: 'Git is not installed', type: 'error', persistent: true);
        }

        if ($repoPath !== null) {
            $this->repoPath = $repoPath;

            if (! empty($this->repoPath) && ! is_dir($this->repoPath.'/.git')) {
                $this->repoPath = $this->loadMostRecentRepo();
            }
        } else {
            $repoManager = app(\App\Services\RepoManager::class);
            $currentRepo = $repoManager->currentRepo();

            if ($currentRepo && is_dir($currentRepo->path.'/.git')) {
                $this->repoPath = $currentRepo->path;
            } else {
                $this->repoPath = $this->loadMostRecentRepo();
            }
        }

        $this->previousRepoPath = $this->repoPath;
    }

    private function loadMostRecentRepo(): string
    {
        $repoManager = app(\App\Services\RepoManager::class);
        $recentRepos = $repoManager->recentRepos(1);

        if ($recentRepos->isEmpty()) {
            return '';
        }

        $mostRecent = $recentRepos->first();

        if (is_dir($mostRecent->path.'/.git')) {
            return $mostRecent->path;
        }

        return '';
    }

    public function toggleSidebar(): void
    {
        $this->sidebarCollapsed = ! $this->sidebarCollapsed;
    }

    #[On('palette-toggle-sidebar')]
    public function handlePaletteToggleSidebar(): void
    {
        $this->toggleSidebar();
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        if ($this->previousRepoPath && $this->previousRepoPath !== $path) {
            $cache = new GitCacheService;
            $cache->invalidateAll($this->previousRepoPath);
        }

        $this->previousRepoPath = $path;
        $this->repoPath = $path;
    }

    public function getStatusBarDataProperty(): array
    {
        if (empty($this->repoPath)) {
            return [];
        }

        try {
            $gitService = new \App\Services\Git\GitService($this->repoPath);
            $status = $gitService->status();

            return [
                'branch' => $status->branch,
                'ahead' => $status->aheadBehind->ahead,
                'behind' => $status->aheadBehind->behind,
            ];
        } catch (\Exception $e) {
            return [];
        }
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.app-layout');
    }
}
