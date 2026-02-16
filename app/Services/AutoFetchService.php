<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Git\GitOperationQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Process;

class AutoFetchService
{
    protected string $repoPath;

    protected int $interval = 180;

    public function __construct(?string $repoPath = null)
    {
        if ($repoPath !== null) {
            $this->repoPath = $repoPath;
            $this->loadConfigFromCache();
        }
    }

    public function start(string $repoPath, int $intervalSeconds = 180): void
    {
        $gitDir = rtrim($repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$repoPath}");
        }

        $this->repoPath = $repoPath;

        if ($intervalSeconds === 0) {
            $this->stop();

            return;
        }

        if ($intervalSeconds < 60) {
            $intervalSeconds = 60;
        }

        $this->interval = $intervalSeconds;

        $cacheKey = $this->getCacheKey('interval');
        Cache::put($cacheKey, $intervalSeconds);

        $cacheKey = $this->getCacheKey('repo-path');
        Cache::put($cacheKey, $repoPath);
    }

    public function stop(): void
    {
        if (! isset($this->repoPath)) {
            return;
        }

        Cache::forget($this->getCacheKey('interval'));
        Cache::forget($this->getCacheKey('repo-path'));
        Cache::forget($this->getCacheKey('last-fetch'));
    }

    public function isRunning(): bool
    {
        if (! isset($this->repoPath)) {
            return false;
        }

        $intervalKey = $this->getCacheKey('interval');
        $interval = Cache::get($intervalKey);

        return $interval !== null && $interval > 0;
    }

    public function shouldFetch(): bool
    {
        if (! $this->isRunning()) {
            return false;
        }

        $queue = new GitOperationQueue($this->repoPath);
        if ($queue->isLocked()) {
            return false;
        }

        $lastFetchTime = $this->getLastFetchTime();
        if ($lastFetchTime === null) {
            return true;
        }

        $intervalKey = $this->getCacheKey('interval');
        $interval = Cache::get($intervalKey, 180);

        return now()->diffInSeconds($lastFetchTime, true) >= $interval;
    }

    public function executeFetch(): array
    {
        if (! isset($this->repoPath)) {
            return [
                'success' => false,
                'output' => '',
                'error' => 'Auto-fetch not started',
            ];
        }

        $result = Process::path($this->repoPath)->run('git fetch --all');

        $success = $result->exitCode() === 0;
        $output = trim($result->output());
        $error = $success ? '' : trim($result->errorOutput() ?: $result->output());

        if ($success) {
            $lastFetchKey = $this->getCacheKey('last-fetch');
            Cache::put($lastFetchKey, now()->timestamp);
        }

        return [
            'success' => $success,
            'output' => $output,
            'error' => $error,
        ];
    }

    public function getLastFetchTime(): ?Carbon
    {
        if (! isset($this->repoPath)) {
            return null;
        }

        $lastFetchKey = $this->getCacheKey('last-fetch');
        $timestamp = Cache::get($lastFetchKey);

        return $timestamp !== null ? Carbon::createFromTimestamp($timestamp) : null;
    }

    public function getNextFetchTime(): ?Carbon
    {
        if (! $this->isRunning()) {
            return null;
        }

        $lastFetchTime = $this->getLastFetchTime();
        if ($lastFetchTime === null) {
            return now();
        }

        $intervalKey = $this->getCacheKey('interval');
        $interval = Cache::get($intervalKey, 180);

        return $lastFetchTime->copy()->addSeconds($interval);
    }

    protected function getCacheKey(string $suffix): string
    {
        $repoHash = md5($this->repoPath);

        return "auto-fetch:{$repoHash}:{$suffix}";
    }

    protected function loadConfigFromCache(): void
    {
        $intervalKey = $this->getCacheKey('interval');
        $this->interval = Cache::get($intervalKey, 180);
    }
}
