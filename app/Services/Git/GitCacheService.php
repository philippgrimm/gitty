<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Cache;

class GitCacheService
{
    private const CACHE_PREFIX = 'gitty';

    private const GROUPS = [
        'status' => ['status', 'diff'],
        'history' => ['log'],
        'branches' => ['branches'],
        'remotes' => ['remotes'],
        'stashes' => ['stashes'],
        'tags' => ['tags'],
    ];

    public function get(string $repoPath, string $key, callable $callback, int $ttl): mixed
    {
        $cacheKey = $this->buildCacheKey($repoPath, $key);

        return Cache::remember($cacheKey, $ttl, $callback);
    }

    public function invalidate(string $repoPath, string $key): void
    {
        $cacheKey = $this->buildCacheKey($repoPath, $key);
        Cache::forget($cacheKey);
    }

    public function invalidateAll(string $repoPath): void
    {
        $hash = md5($repoPath);
        $prefix = self::CACHE_PREFIX.":{$hash}:";

        $allKeys = array_merge(
            ['status', 'diff', 'log', 'branches', 'remotes', 'stashes', 'tags']
        );

        foreach ($allKeys as $key) {
            Cache::forget($prefix.$key);
        }
    }

    public function invalidateGroup(string $repoPath, string $group): void
    {
        if (! isset(self::GROUPS[$group])) {
            return;
        }

        $keys = self::GROUPS[$group];
        foreach ($keys as $key) {
            $this->invalidate($repoPath, $key);
        }
    }

    private function buildCacheKey(string $repoPath, string $key): string
    {
        $hash = md5($repoPath);

        return self::CACHE_PREFIX.":{$hash}:{$key}";
    }
}
