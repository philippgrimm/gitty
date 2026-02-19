<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\SearchService;
use Livewire\Attributes\On;
use Livewire\Component;

class SearchPanel extends Component
{
    public string $repoPath = '';

    public string $query = '';

    public string $scope = 'commits';

    public array $results = [];

    public bool $isOpen = false;

    public int $selectedIndex = 0;

    #[On('open-search')]
    public function open(): void
    {
        $this->isOpen = true;
        $this->query = '';
        $this->results = [];
        $this->selectedIndex = 0;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->results = [];
        $this->selectedIndex = 0;
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
        $this->query = '';
        $this->results = [];
        $this->selectedIndex = 0;
    }

    public function setScope(string $scope): void
    {
        $this->scope = $scope;
        $this->search();
    }

    public function updatedQuery(): void
    {
        if (strlen($this->query) >= 3) {
            $this->search();
        } else {
            $this->results = [];
        }
        $this->selectedIndex = 0;
    }

    public function search(): void
    {
        if (empty($this->repoPath) || strlen($this->query) < 3) {
            $this->results = [];

            return;
        }

        try {
            $service = new SearchService($this->repoPath);

            $this->results = match ($this->scope) {
                'commits' => $service->searchCommits($this->query)->toArray(),
                'content' => $service->searchContent($this->query)->toArray(),
                'files' => $service->searchFiles($this->query)->toArray(),
                default => [],
            };
        } catch (\Exception $e) {
            $this->results = [];
        }
    }

    public function selectResult(string $identifier): void
    {
        if ($this->scope === 'files') {
            $this->dispatch('file-selected', path: $identifier);
        } else {
            $this->dispatch('commit-selected', sha: $identifier);
        }

        $this->close();
    }

    public function render()
    {
        return view('livewire.search-panel');
    }
}
