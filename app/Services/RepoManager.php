<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RepoManager
{
    public function openRepo(string $path): Repository
    {
        $gitDir = rtrim($path, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$path}");
        }

        $name = basename($path);

        $repo = Repository::firstOrCreate(
            ['path' => $path],
            ['name' => $name]
        );

        $repo->update(['last_opened_at' => now()]);
        $repo->refresh();

        $this->setCurrentRepo($repo);

        return $repo;
    }

    public function recentRepos(int $limit = 20): Collection
    {
        return Repository::whereNotNull('last_opened_at')
            ->orderBy('last_opened_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public function removeRepo(int $id): void
    {
        Repository::destroy($id);
    }

    public function currentRepo(): ?Repository
    {
        $repoId = Cache::get('current_repo_id');
        
        if ($repoId === null) {
            return null;
        }

        return Repository::find($repoId);
    }

    public function setCurrentRepo(Repository $repo): void
    {
        Cache::put('current_repo_id', $repo->id);
    }
}
