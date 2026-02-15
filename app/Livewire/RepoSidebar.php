<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\BranchService;
use App\Services\Git\GitService;
use App\Services\Git\RemoteService;
use App\Services\Git\StashService;
use Illuminate\Support\Facades\Process;
use Livewire\Component;

class RepoSidebar extends Component
{
    public string $repoPath;

    public array $branches = [];

    public array $remotes = [];

    public array $tags = [];

    public array $stashes = [];

    public string $currentBranch = '';

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
        $this->currentBranch = $status->branch;

        $this->branches = $branchService->branches()
            ->filter(fn ($branch) => ! $branch->isRemote)
            ->map(fn ($branch) => [
                'name' => $branch->name,
                'isCurrent' => $branch->isCurrent,
                'lastCommitSha' => $branch->lastCommitSha,
            ])
            ->toArray();

        $this->remotes = $remoteService->remotes()
            ->map(fn ($remote) => [
                'name' => $remote->name,
                'fetchUrl' => $remote->fetchUrl,
                'pushUrl' => $remote->pushUrl,
            ])
            ->toArray();

        $this->tags = $this->fetchTags();

        $this->stashes = $stashService->stashList()
            ->map(fn ($stash) => [
                'index' => $stash->index,
                'message' => $stash->message,
                'branch' => $stash->branch,
                'sha' => $stash->sha,
            ])
            ->toArray();
    }

    public function switchBranch(string $name): void
    {
        $branchService = new BranchService($this->repoPath);
        $branchService->switchBranch($name);

        $this->refreshSidebar();
        $this->dispatch('status-updated');
    }

    private function fetchTags(): array
    {
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
    }

    public function render()
    {
        return view('livewire.repo-sidebar');
    }
}
