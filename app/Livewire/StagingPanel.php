<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\GitService;
use App\Services\Git\StagingService;
use Illuminate\Support\Collection;
use Livewire\Component;

class StagingPanel extends Component
{
    public string $repoPath;

    public Collection $unstagedFiles;

    public Collection $stagedFiles;

    public Collection $untrackedFiles;

    private bool $pausePolling = false;

    public function mount(): void
    {
        $this->unstagedFiles = collect();
        $this->stagedFiles = collect();
        $this->untrackedFiles = collect();
        $this->refreshStatus();
    }

    public function refreshStatus(): void
    {
        if ($this->pausePolling) {
            return;
        }

        $gitService = new GitService($this->repoPath);
        $status = $gitService->status();

        $this->unstagedFiles = collect();
        $this->stagedFiles = collect();
        $this->untrackedFiles = collect();

        foreach ($status->changedFiles as $file) {
            $indexStatus = $file['indexStatus'];
            $worktreeStatus = $file['worktreeStatus'];
            $path = $file['path'];

            if ($indexStatus === '?' && $worktreeStatus === '?') {
                $this->untrackedFiles->push($file);
            } else {
                if ($worktreeStatus !== '.') {
                    $this->unstagedFiles->push($file);
                }
                if ($indexStatus !== '.' && $indexStatus !== '?') {
                    $this->stagedFiles->push($file);
                }
            }
        }
    }

    public function stageFile(string $file): void
    {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->stageFile($file);
        $this->pausePollingTemporarily();
        $this->refreshStatus();
        $this->dispatch('status-updated');
    }

    public function unstageFile(string $file): void
    {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->unstageFile($file);
        $this->pausePollingTemporarily();
        $this->refreshStatus();
        $this->dispatch('status-updated');
    }

    public function stageAll(): void
    {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->stageAll();
        $this->pausePollingTemporarily();
        $this->refreshStatus();
        $this->dispatch('status-updated');
    }

    public function unstageAll(): void
    {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->unstageAll();
        $this->pausePollingTemporarily();
        $this->refreshStatus();
        $this->dispatch('status-updated');
    }

    public function discardFile(string $file): void
    {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->discardFile($file);
        $this->pausePollingTemporarily();
        $this->refreshStatus();
        $this->dispatch('status-updated');
    }

    public function discardAll(): void
    {
        $stagingService = new StagingService($this->repoPath);
        $stagingService->discardAll();
        $this->pausePollingTemporarily();
        $this->refreshStatus();
        $this->dispatch('status-updated');
    }

    public function selectFile(string $file, bool $staged): void
    {
        $this->dispatch('file-selected', file: $file, staged: $staged);
    }

    public function render()
    {
        return view('livewire.staging-panel');
    }

    private function pausePollingTemporarily(): void
    {
        $this->pausePolling = true;
        $this->dispatch('$refresh')->self();
    }

    public function resumePolling(): void
    {
        $this->pausePolling = false;
    }
}
