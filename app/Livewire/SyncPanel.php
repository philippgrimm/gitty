<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\GitErrorHandler;
use App\Services\Git\GitService;
use Illuminate\Support\Facades\Process;
use Livewire\Attributes\On;
use Livewire\Component;

class SyncPanel extends Component
{
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
            $this->aheadBehind = $status->aheadBehind;
        } catch (\Exception $e) {
            $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        }
    }

    #[On('status-updated')]
    #[On('remote-updated')]
    public function refreshAheadBehind(int $stagedCount = 0, array $aheadBehind = []): void
    {
        if (! empty($aheadBehind)) {
            $this->aheadBehind = $aheadBehind;
        }
    }

    private function refreshAheadBehindData(): void
    {
        try {
            $gitService = new GitService($this->repoPath);
            $status = $gitService->status();
            $this->aheadBehind = $status->aheadBehind;
        } catch (\Exception $e) {
            $this->aheadBehind = ['ahead' => 0, 'behind' => 0];
        }
    }

    public function syncPush(): void
    {
        $this->error = '';
        $this->isOperationRunning = true;

        try {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                $this->error = 'Cannot push from detached HEAD state';
                $this->isOperationRunning = false;

                return;
            }

            $result = Process::path($this->repoPath)->run("git push origin {$currentBranch}");

            if ($result->exitCode() !== 0) {
                $errorMsg = trim($result->errorOutput() ?: $result->output());
                $this->error = GitErrorHandler::translate($errorMsg);
                $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'push';
            $this->isOperationRunning = false;
            $this->refreshAheadBehindData();
            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $this->aheadBehind);
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
            $this->isOperationRunning = false;
        }
    }

    public function syncPull(): void
    {
        $this->error = '';
        $this->isOperationRunning = true;

        try {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                $this->error = 'Cannot pull from detached HEAD state';
                $this->isOperationRunning = false;

                return;
            }

            $result = Process::path($this->repoPath)->run("git pull origin {$currentBranch}");

            if ($result->exitCode() !== 0) {
                $errorMsg = trim($result->errorOutput() ?: $result->output());
                $this->error = GitErrorHandler::translate($errorMsg);
                $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'pull';
            $this->isOperationRunning = false;
            $this->refreshAheadBehindData();
            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $this->aheadBehind);
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
            $this->isOperationRunning = false;
        }
    }

    public function syncFetch(): void
    {
        $this->error = '';
        $this->isOperationRunning = true;

        try {
            $result = Process::path($this->repoPath)->run('git fetch origin');

            if ($result->exitCode() !== 0) {
                $errorMsg = trim($result->errorOutput() ?: $result->output());
                $this->error = GitErrorHandler::translate($errorMsg);
                $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'fetch';
            $this->isOperationRunning = false;
            $this->refreshAheadBehindData();
            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $this->aheadBehind);
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
            $this->isOperationRunning = false;
        }
    }

    public function syncFetchAll(): void
    {
        $this->error = '';
        $this->isOperationRunning = true;

        try {
            $result = Process::path($this->repoPath)->run('git fetch --all');

            if ($result->exitCode() !== 0) {
                $errorMsg = trim($result->errorOutput() ?: $result->output());
                $this->error = GitErrorHandler::translate($errorMsg);
                $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'fetch-all';
            $this->isOperationRunning = false;
            $this->refreshAheadBehindData();
            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $this->aheadBehind);
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
            $this->isOperationRunning = false;
        }
    }

    public function syncForcePushWithLease(): void
    {
        $this->error = '';
        $this->isOperationRunning = true;

        try {
            $gitService = new GitService($this->repoPath);
            $currentBranch = $gitService->currentBranch();

            if ($gitService->isDetachedHead()) {
                $this->error = 'Cannot push from detached HEAD state';
                $this->isOperationRunning = false;

                return;
            }

            $result = Process::path($this->repoPath)->run("git push --force-with-lease origin {$currentBranch}");

            if ($result->exitCode() !== 0) {
                $errorMsg = trim($result->errorOutput() ?: $result->output());
                $this->error = GitErrorHandler::translate($errorMsg);
                $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'force-push';
            $this->isOperationRunning = false;
            $this->refreshAheadBehindData();
            $this->dispatch('status-updated', stagedCount: 0, aheadBehind: $this->aheadBehind);
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);
            $this->isOperationRunning = false;
        }
    }

    public function render()
    {
        return view('livewire.sync-panel');
    }
}
