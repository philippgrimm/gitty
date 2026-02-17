<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Livewire\Concerns\HandlesGitOperations;
use App\Services\Git\GitService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Process;
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
        } catch (\Exception $e) {
            $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        }
    }

    #[On('status-updated')]
    #[On('remote-updated')]
    public function refreshAheadBehind(int $stagedCount = 0, array $aheadBehind = []): void
    {
        if (! empty($aheadBehind) && array_key_exists('ahead', $aheadBehind) && array_key_exists('behind', $aheadBehind)) {
            $this->aheadBehind = $aheadBehind;
        }
    }

    private function refreshAheadBehindData(): void
    {
        try {
            $gitService = new GitService($this->repoPath);
            $status = $gitService->status();
            $this->aheadBehind = ['ahead' => $status->aheadBehind->ahead, 'behind' => $status->aheadBehind->behind];
        } catch (\Exception $e) {
            $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        }
    }

    public function syncPush(): void
    {
        $this->executeSyncOperation(function () {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                throw new \RuntimeException('Cannot push from detached HEAD state');
            }

            $result = Process::path($this->repoPath)->run("git push origin {$currentBranch}");

            if ($result->exitCode() !== 0) {
                throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
            }

            $this->operationOutput = trim($result->output());

            $commitCount = $this->aheadBehind['ahead'] ?? 0;
            app(NotificationService::class)->notify(
                'Push Complete',
                "Pushed {$commitCount} commit(s) to origin/{$currentBranch}"
            );
        }, 'push');
    }

    public function syncPull(): void
    {
        $this->executeSyncOperation(function () {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                throw new \RuntimeException('Cannot pull from detached HEAD state');
            }

            $result = Process::path($this->repoPath)->run("git pull origin {$currentBranch}");

            if ($result->exitCode() !== 0) {
                throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
            }

            $this->operationOutput = trim($result->output());

            app(NotificationService::class)->notify(
                'Pull Complete',
                "Pulled new commits from origin/{$currentBranch}"
            );
        }, 'pull');
    }

    public function syncFetch(): void
    {
        $this->executeSyncOperation(function () {
            $result = Process::path($this->repoPath)->run('git fetch origin');

            if ($result->exitCode() !== 0) {
                throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
            }

            $this->operationOutput = trim($result->output());
        }, 'fetch');
    }

    public function syncFetchAll(): void
    {
        $this->executeSyncOperation(function () {
            $result = Process::path($this->repoPath)->run('git fetch --all');

            if ($result->exitCode() !== 0) {
                throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
            }

            $this->operationOutput = trim($result->output());
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

            $result = Process::path($this->repoPath)->run("git push --force-with-lease origin {$currentBranch}");

            if ($result->exitCode() !== 0) {
                throw new \RuntimeException(trim($result->errorOutput() ?: $result->output()));
            }

            $this->operationOutput = trim($result->output());
        }, 'force-push');
    }

    private function executeSyncOperation(callable $operation, string $operationName): void
    {
        $this->error = '';
        $this->isOperationRunning = true;

        $this->executeGitOperation(function () use ($operation, $operationName) {
            $operation();
            $this->lastOperation = $operationName;
            $this->refreshAheadBehindData();
        }, dispatchStatusUpdate: false);

        $this->isOperationRunning = false;

        if (empty($this->error)) {
            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $this->aheadBehind);
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

    public function render()
    {
        return view('livewire.sync-panel');
    }
}
