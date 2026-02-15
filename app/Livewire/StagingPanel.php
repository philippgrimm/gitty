<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Helpers\FileTreeBuilder;
use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use App\Services\Git\StagingService;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;

class StagingPanel extends Component
{
    public string $repoPath;

    public Collection $unstagedFiles;

    public Collection $stagedFiles;

    public Collection $untrackedFiles;

    public bool $treeView = false;

    public string $error = '';

    private bool $pausePolling = false;

    private ?array $lastAheadBehind = null;

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

        try {
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

            $this->lastAheadBehind = $status->aheadBehind;
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function stageFile(string $file): void
    {
        try {
            $stagingService = new StagingService($this->repoPath);
            $stagingService->stageFile($file);
            $this->pausePollingTemporarily();
            $this->refreshStatus();
            $this->dispatch('status-updated',
                stagedCount: $this->stagedFiles->count(),
                aheadBehind: $this->lastAheadBehind ?? ['ahead' => 0, 'behind' => 0],
            );
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function unstageFile(string $file): void
    {
        try {
            $stagingService = new StagingService($this->repoPath);
            $stagingService->unstageFile($file);
            $this->pausePollingTemporarily();
            $this->refreshStatus();
            $this->dispatch('status-updated',
                stagedCount: $this->stagedFiles->count(),
                aheadBehind: $this->lastAheadBehind ?? ['ahead' => 0, 'behind' => 0],
            );
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function stageAll(): void
    {
        try {
            $stagingService = new StagingService($this->repoPath);
            $stagingService->stageAll();
            $this->pausePollingTemporarily();
            $this->refreshStatus();
            $this->dispatch('status-updated',
                stagedCount: $this->stagedFiles->count(),
                aheadBehind: $this->lastAheadBehind ?? ['ahead' => 0, 'behind' => 0],
            );
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function unstageAll(): void
    {
        try {
            $stagingService = new StagingService($this->repoPath);
            $stagingService->unstageAll();
            $this->pausePollingTemporarily();
            $this->refreshStatus();
            $this->dispatch('status-updated',
                stagedCount: $this->stagedFiles->count(),
                aheadBehind: $this->lastAheadBehind ?? ['ahead' => 0, 'behind' => 0],
            );
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function discardFile(string $file): void
    {
        try {
            $stagingService = new StagingService($this->repoPath);
            $stagingService->discardFile($file);
            $this->pausePollingTemporarily();
            $this->refreshStatus();
            $this->dispatch('status-updated',
                stagedCount: $this->stagedFiles->count(),
                aheadBehind: $this->lastAheadBehind ?? ['ahead' => 0, 'behind' => 0],
            );
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function discardAll(): void
    {
        try {
            $stagingService = new StagingService($this->repoPath);
            $stagingService->discardAll();
            $this->pausePollingTemporarily();
            $this->refreshStatus();
            $this->dispatch('status-updated',
                stagedCount: $this->stagedFiles->count(),
                aheadBehind: $this->lastAheadBehind ?? ['ahead' => 0, 'behind' => 0],
            );
            $this->error = '';
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
        }
    }

    public function selectFile(string $file, bool $staged): void
    {
        $this->dispatch('file-selected', file: $file, staged: $staged);
    }

    public function toggleView(): void
    {
        $this->treeView = ! $this->treeView;
    }

    public function render()
    {
        $stagedTree = $this->treeView && $this->stagedFiles->isNotEmpty()
            ? FileTreeBuilder::buildTree($this->stagedFiles->toArray())
            : [];

        $unstagedTree = $this->treeView && ($this->unstagedFiles->isNotEmpty() || $this->untrackedFiles->isNotEmpty())
            ? FileTreeBuilder::buildTree($this->unstagedFiles->concat($this->untrackedFiles)->toArray())
            : [];

        return view('livewire.staging-panel', [
            'stagedTree' => $stagedTree,
            'unstagedTree' => $unstagedTree,
        ]);
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

    #[On('keyboard-stage-all')]
    public function handleKeyboardStageAll(): void
    {
        $this->stageAll();
    }

    #[On('keyboard-unstage-all')]
    public function handleKeyboardUnstageAll(): void
    {
        $this->unstageAll();
    }

    #[On('refresh-staging')]
    public function handleRefreshStaging(): void
    {
        $this->refreshStatus();
        $this->dispatch('status-updated',
            stagedCount: $this->stagedFiles->count(),
            aheadBehind: $this->lastAheadBehind ?? ['ahead' => 0, 'behind' => 0],
        );
    }
}
