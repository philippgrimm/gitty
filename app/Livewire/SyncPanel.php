<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HandlesGitOperations;
use App\Services\Git\GitService;
use App\Services\Git\RemoteService;
use App\Services\NotificationService;
use Livewire\Attributes\On;
use Livewire\Component;

class SyncPanel extends Component
{
    use HandlesGitOperations;

    public string $repoPath;

    public bool $isOperationRunning = false;

    public string $operationOutput = '';

    public string $error = '';

    public string $lastOperation = '';

    public array $aheadBehind = ['ahead' => 0, 'behind' => 0];

    public bool $hasUpstream = false;

    public function mount(): void
    {
        $this->isOperationRunning = false;
        $this->error = '';
        $this->operationOutput = '';
        $this->lastOperation = '';
        try {
            $gitService = new GitService($this->repoPath);
            $status = $gitService->status();
            $this->aheadBehind = ['ahead' => $status->aheadBehind->ahead, 'behind' => $status->aheadBehind->behind];
            $this->hasUpstream = $status->upstream !== null;
        } catch (\Exception $e) {
            $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        }
    }

    #[On('status-updated')]
    #[On('remote-updated')]
    public function refreshAheadBehind(int $stagedCount = 0, array $aheadBehind = [], ?bool $hasUpstream = null): void
    {
        if (! empty($aheadBehind) && array_key_exists('ahead', $aheadBehind) && array_key_exists('behind', $aheadBehind)) {
            $this->aheadBehind = $aheadBehind;
        } else {
            $this->refreshAheadBehindData();
        }

        if ($hasUpstream !== null) {
            $this->hasUpstream = $hasUpstream;
        }
    }

    #[On('committed')]
    public function handleCommitted(): void
    {
        $this->refreshAheadBehindData();
    }

    private function refreshAheadBehindData(): void
    {
        try {
            $gitService = new GitService($this->repoPath);
            $status = $gitService->status();
            $this->aheadBehind = ['ahead' => $status->aheadBehind->ahead, 'behind' => $status->aheadBehind->behind];
            $this->hasUpstream = $status->upstream !== null;
        } catch (\Exception $e) {
            $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        }
    }

    public function syncPush(): void
    {
        $gitService = new GitService($this->repoPath);

        if ($gitService->isDetachedHead()) {
            $this->error = 'Cannot push from detached HEAD state';

            return;
        }

        if (! $this->hasUpstream) {
            $this->publishBranch();

            return;
        }

        $commitCount = $this->aheadBehind['ahead'] ?? 0;
        $currentBranch = null;

        $this->executeSyncOperation(function () use (&$currentBranch, $gitService) {
            $currentBranch = $gitService->currentBranch();

            $remoteService = new RemoteService($this->repoPath);

            return $remoteService->push('origin', $currentBranch);
        }, 'push');

        if (empty($this->error) && $currentBranch) {
            app(NotificationService::class)->notify(
                'Push Complete',
                "Pushed {$commitCount} commit(s) to origin/{$currentBranch}"
            );
        }
    }

    public function publishBranch(): void
    {
        $currentBranch = null;

        $this->executeSyncOperation(function () use (&$currentBranch) {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                throw new \RuntimeException('Cannot publish from detached HEAD state');
            }

            $remoteService = new RemoteService($this->repoPath);

            return $remoteService->pushSetUpstream('origin', $currentBranch);
        }, 'publish');

        if (empty($this->error) && $currentBranch) {
            app(NotificationService::class)->notify(
                'Branch Published',
                "Published {$currentBranch} to origin/{$currentBranch}"
            );
        }
    }

    public function syncPull(): void
    {
        $currentBranch = null;

        $this->executeSyncOperation(function () use (&$currentBranch) {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                throw new \RuntimeException('Cannot pull from detached HEAD state');
            }

            $remoteService = new RemoteService($this->repoPath);

            return $remoteService->pull('origin', $currentBranch);
        }, 'pull');

        if (empty($this->error) && $currentBranch) {
            app(NotificationService::class)->notify(
                'Pull Complete',
                "Pulled new commits from origin/{$currentBranch}"
            );
        }
    }

    public function syncFetch(): void
    {
        $this->executeSyncOperation(function () {
            $remoteService = new RemoteService($this->repoPath);

            return $remoteService->fetch('origin');
        }, 'fetch');
    }

    public function syncFetchAll(): void
    {
        $this->executeSyncOperation(function () {
            $remoteService = new RemoteService($this->repoPath);

            return $remoteService->fetchAll();
        }, 'fetch-all');
    }

    public function syncForcePushWithLease(): void
    {
        $this->executeSyncOperation(function () {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                throw new \RuntimeException('Cannot push from detached HEAD state');
            }

            $remoteService = new RemoteService($this->repoPath);

            return $remoteService->forcePushWithLease('origin', $currentBranch);
        }, 'force-push');
    }

    private function executeSyncOperation(callable $operation, string $operationName): void
    {
        $this->error = '';
        $this->operationOutput = '';
        $this->isOperationRunning = true;

        $this->executeGitOperation(function () use ($operation, $operationName) {
            $output = $operation();
            $this->operationOutput = is_string($output) ? trim($output) : '';
            $this->lastOperation = $operationName;
            $this->refreshAheadBehindData();
        }, dispatchStatusUpdate: false);

        $this->isOperationRunning = false;

        if (empty($this->error)) {
            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $this->aheadBehind, hasUpstream: $this->hasUpstream);
        }
    }

    #[On('palette-push')]
    public function handlePalettePush(): void
    {
        $this->syncPush();
    }

    #[On('palette-pull')]
    public function handlePalettePull(): void
    {
        $this->syncPull();
    }

    #[On('palette-fetch')]
    public function handlePaletteFetch(): void
    {
        $this->syncFetch();
    }

    #[On('palette-fetch-all')]
    public function handlePaletteFetchAll(): void
    {
        $this->syncFetchAll();
    }

    #[On('palette-force-push')]
    public function handlePaletteForcePush(): void
    {
        $this->syncForcePushWithLease();
    }

    #[On('palette-publish')]
    public function handlePalettePublish(): void
    {
        $this->publishBranch();
    }

    public function render()
    {
        return view('livewire.sync-panel');
    }
}
