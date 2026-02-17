<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Collection;

class TagService extends AbstractGitService
{
    public function tags(): Collection
    {
        return $this->cache->get(
            $this->repoPath,
            'tags',
            function () {
                $result = $this->commandRunner
                    ->run("tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'");

                if (! $result->successful()) {
                    return collect();
                }

                return collect(array_filter(explode("\n", trim($result->output()))))
                    ->map(function (string $line) {
                        $parts = explode('|||', $line);

                        return [
                            'name' => $parts[0] ?? '',
                            'sha' => $parts[1] ?? '',
                            'date' => $parts[2] ?? '',
                            'message' => $parts[3] ?? '',
                        ];
                    });
            },
            60
        );
    }

    public function createTag(string $name, ?string $message = null, ?string $commit = null): void
    {
        if ($message) {
            $args = [$name, '-m', $message];
            if ($commit) {
                $args[] = $commit;
            }
            $result = $this->commandRunner->run('tag -a', $args);
        } else {
            $args = [$name];
            if ($commit) {
                $args[] = $commit;
            }
            $result = $this->commandRunner->run('tag', $args);
        }

        if (! $result->successful()) {
            throw new \RuntimeException('Failed to create tag: '.$result->errorOutput());
        }
        $this->cache->invalidateGroup($this->repoPath, 'tags');
    }

    public function deleteTag(string $name): void
    {
        $result = $this->commandRunner->run('tag -d', [$name]);
        if (! $result->successful()) {
            throw new \RuntimeException('Failed to delete tag: '.$result->errorOutput());
        }
        $this->cache->invalidateGroup($this->repoPath, 'tags');
    }

    public function pushTag(string $name, string $remote = 'origin'): void
    {
        $result = $this->commandRunner->run('push', [$remote, $name]);
        if (! $result->successful()) {
            throw new \RuntimeException('Failed to push tag: '.$result->errorOutput());
        }
    }
}
