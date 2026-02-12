<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\Git\GitService;
use Illuminate\Support\Facades\Process;
use Livewire\Component;

class SyncPanel extends Component
{
    public string $repoPath;

    public bool $isOperationRunning = false;

    public string $operationOutput = '';

    public string $error = '';

    public string $lastOperation = '';

    public function mount(): void
    {
        $this->isOperationRunning = false;
        $this->error = '';
        $this->operationOutput = '';
        $this->lastOperation = '';
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
                $this->error = 'Push failed: ' . $errorMsg;
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'push';
            $this->isOperationRunning = false;
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = 'Push failed: ' . $e->getMessage();
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
                $this->error = 'Pull failed: ' . $errorMsg;
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'pull';
            $this->isOperationRunning = false;
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = 'Pull failed: ' . $e->getMessage();
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
                $this->error = 'Fetch failed: ' . $errorMsg;
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'fetch';
            $this->isOperationRunning = false;
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = 'Fetch failed: ' . $e->getMessage();
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
                $this->error = 'Fetch all failed: ' . $errorMsg;
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'fetch-all';
            $this->isOperationRunning = false;
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = 'Fetch all failed: ' . $e->getMessage();
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
                $this->error = 'Force push failed: ' . $errorMsg;
                $this->isOperationRunning = false;

                return;
            }

            $this->operationOutput = trim($result->output());
            $this->lastOperation = 'force-push';
            $this->isOperationRunning = false;
            $this->dispatch('status-updated');
        } catch (\Exception $e) {
            $this->error = 'Force push failed: ' . $e->getMessage();
            $this->isOperationRunning = false;
        }
    }

    public function render()
    {
        return view('livewire.sync-panel');
    }
}
