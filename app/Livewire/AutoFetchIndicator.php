<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\AutoFetchService;
use App\Services\Git\GitOperationQueue;
use Livewire\Component;

class AutoFetchIndicator extends Component
{
    public string $repoPath;

    public bool $isActive = false;

    public string $lastFetchAt = '';

    public string $lastError = '';

    public bool $isFetching = false;

    public bool $isQueueLocked = false;

    public function mount(): void
    {
        $this->checkAndFetch();
    }

    public function refreshStatus(): void
    {
        $service = new AutoFetchService($this->repoPath);

        $this->isActive = $service->isRunning();

        $queue = new GitOperationQueue($this->repoPath);
        $this->isQueueLocked = $queue->isLocked();

        $lastFetchTime = $service->getLastFetchTime();
        if ($lastFetchTime !== null) {
            $this->lastFetchAt = $lastFetchTime->diffForHumans();
        } else {
            $this->lastFetchAt = 'Never';
        }
    }

    public function checkAndFetch(): void
    {
        $service = new AutoFetchService($this->repoPath);

        if (! $service->shouldFetch()) {
            $this->refreshStatus();

            return;
        }

        $this->isFetching = true;
        $this->lastError = '';

        $result = $service->executeFetch();

        $this->isFetching = false;

        if ($result['success']) {
            $this->dispatch('remote-updated');
        } else {
            $this->lastError = $result['error'];

            $notificationService = app(NotificationService::class);
            $notificationService->notify(
                'Fetch Failed',
                $result['error']
            );
        }

        $this->refreshStatus();
    }

    public function render()
    {
        return view('livewire.auto-fetch-indicator');
    }
}
