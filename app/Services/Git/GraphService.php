<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\GraphNode;
use Illuminate\Support\Facades\Process;

class GraphService
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/').'/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function getGraphData(int $limit = 200): array
    {
        $result = Process::path($this->repoPath)->run(
            "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n {$limit}"
        );

        if ($result->exitCode() !== 0) {
            return [];
        }

        $lines = array_filter(explode("\n", trim($result->output())));
        $nodes = [];
        $commitToLane = [];
        $activeLanes = [];
        $nextLane = 0;

        foreach ($lines as $line) {
            $parts = explode('|||', $line);

            $sha = $parts[0] ?? '';
            $parentString = $parts[1] ?? '';
            $author = $parts[2] ?? '';
            $date = $parts[3] ?? '';
            $message = $parts[4] ?? '';
            $refString = $parts[5] ?? '';

            $parents = array_filter(explode(' ', $parentString));
            $refs = [];
            if (! empty($refString)) {
                $refs = array_map('trim', explode(',', $refString));
            }

            $branch = $this->determineBranch($refs);

            $lane = $this->assignLane($sha, $parents, $commitToLane, $activeLanes, $nextLane);

            $nodes[] = new GraphNode(
                sha: $sha,
                parents: $parents,
                branch: $branch,
                refs: $refs,
                message: $message,
                author: $author,
                date: $date,
                lane: $lane,
            );
        }

        return $nodes;
    }

    private function determineBranch(array $refs): string
    {
        foreach ($refs as $ref) {
            if (str_starts_with($ref, 'HEAD -> ')) {
                return str_replace('HEAD -> ', '', $ref);
            }
        }

        foreach ($refs as $ref) {
            if (! str_contains($ref, '/') && ! str_contains($ref, 'tag:')) {
                return $ref;
            }
        }

        return '';
    }

    private function assignLane(
        string $sha,
        array $parents,
        array &$commitToLane,
        array &$activeLanes,
        int &$nextLane
    ): int {
        if (isset($commitToLane[$sha])) {
            return $commitToLane[$sha];
        }

        $lane = 0;

        if (count($parents) === 0) {
            $lane = 0;
        } elseif (count($parents) === 1) {
            $firstParent = $parents[0];
            if (isset($commitToLane[$firstParent])) {
                $lane = $commitToLane[$firstParent];
            } else {
                $lane = 0;
            }
        } else {
            $firstParent = $parents[0];
            if (isset($commitToLane[$firstParent])) {
                $lane = $commitToLane[$firstParent];
            } else {
                $lane = 0;
            }

            for ($i = 1; $i < count($parents); $i++) {
                $parent = $parents[$i];
                if (! isset($commitToLane[$parent])) {
                    $mergeLane = $nextLane++;
                    $commitToLane[$parent] = $mergeLane;
                    $activeLanes[$mergeLane] = $parent;
                }
            }
        }

        $commitToLane[$sha] = $lane;
        $activeLanes[$lane] = $sha;

        if ($nextLane === 0) {
            $nextLane = 1;
        }

        return $lane;
    }

    private function findAvailableLane(array $activeLanes, int &$nextLane): int
    {
        for ($i = 0; $i < $nextLane; $i++) {
            if (! isset($activeLanes[$i])) {
                return $i;
            }
        }

        return $nextLane++;
    }
}
