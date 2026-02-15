<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\RepoManager;
use Livewire\Component;
use Native\Desktop\Dialog;

class RepoSwitcher extends Component
{
    public string $currentRepoPath = '';

    public string $currentRepoName = '';

    public array $recentRepos = [];

    public string $error = '';

    public function mount(): void
    {
        $this->loadCurrentRepo();
        $this->loadRecentRepos();
    }

    public function openRepo(string $path): void
    {
        $this->error = '';

        try {
            $repoManager = new RepoManager;
            $repo = $repoManager->openRepo($path);

            $this->currentRepoPath = $repo->path;
            $this->currentRepoName = $repo->name;

            $this->loadRecentRepos();

            $this->dispatch('repo-switched', path: $repo->path);
        } catch (\InvalidArgumentException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function switchRepo(int $id): void
    {
        $this->error = '';

        $repoManager = new RepoManager;
        $repo = $repoManager->recentRepos()->firstWhere('id', $id);

        if (! $repo) {
            $this->error = 'Repository not found';

            return;
        }

        if (! is_dir($repo->path.'/.git')) {
            $this->error = 'Repository path does not exist or is not a valid git repository';

            return;
        }

        $repo->update(['last_opened_at' => now()]);

        $repoManager->setCurrentRepo($repo);

        $this->currentRepoPath = $repo->path;
        $this->currentRepoName = $repo->name;

        $this->loadRecentRepos();

        $this->dispatch('repo-switched', path: $repo->path);
    }

    public function removeRecentRepo(int $id): void
    {
        $repoManager = new RepoManager;
        $repoManager->removeRepo($id);

        $this->loadRecentRepos();
    }

    public function openFolderDialog(): void
    {
        $path = Dialog::new()
            ->title('Open Git Repository')
            ->button('Open')
            ->folders()
            ->open();

        if (! $path) {
            return;
        }

        $this->openRepo($path);
    }

    private function loadCurrentRepo(): void
    {
        $repoManager = new RepoManager;
        $currentRepo = $repoManager->currentRepo();

        if ($currentRepo) {
            $this->currentRepoPath = $currentRepo->path;
            $this->currentRepoName = $currentRepo->name;
        }
    }

    private function loadRecentRepos(): void
    {
        $repoManager = new RepoManager;
        $this->recentRepos = $repoManager->recentRepos(20)
            ->map(fn ($repo) => [
                'id' => $repo->id,
                'path' => $repo->path,
                'name' => $repo->name,
                'last_opened_at' => $repo->last_opened_at?->diffForHumans(),
            ])
            ->sortBy([
                fn ($a, $b) => ($b['path'] === $this->currentRepoPath) <=> ($a['path'] === $this->currentRepoPath), // Current repo first
                fn ($a, $b) => $b['last_opened_at'] <=> $a['last_opened_at'], // Then by last opened
            ])
            ->values()
            ->toArray();
    }

    public function render()
    {
        return view('livewire.repo-switcher');
    }
}
