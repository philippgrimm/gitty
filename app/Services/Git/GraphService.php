<?php

declare(strict_types=1);

namespace App\Services\Git;

use App\DTOs\GraphNode;
use App\DTOs\HistoryRow;

/**
 * Builds canonical history rows from git's native graph output.
 */
class GraphService extends AbstractGitService
{
    /**
     * @return array<HistoryRow>
     */
    public function getHistoryRows(int $limit = 100, int $skip = 0, string $scope = 'current'): array
    {
        $safeLimit = max(1, $limit);
        $safeSkip = max(0, $skip);
        $safeScope = $scope === 'all' ? 'all' : 'current';

        $result = $this->commandRunner->run($this->buildHistoryCommand($safeLimit, $safeSkip, $safeScope));

        if ($result->exitCode() !== 0) {
            return [];
        }

        return $this->parseHistoryRows($result->output());
    }

    /**
     * Legacy compatibility method used by existing tests and callers.
     *
     * @return array<GraphNode>
     */
    public function getGraphData(int $limit = 200): array
    {
        $rows = $this->getHistoryRows(limit: $limit, skip: 0, scope: 'all');
        $graphNodes = [];

        foreach ($rows as $row) {
            $commitLane = array_search('*', $row->graphCells, true);
            $lane = is_int($commitLane) ? $commitLane : 0;

            $graphNodes[] = new GraphNode(
                sha: $row->sha,
                parents: $row->parents,
                branch: $this->determineBranch($row->refs),
                refs: $row->refs,
                message: $row->message,
                author: $row->author,
                date: $row->date,
                lane: $lane,
            );
        }

        return $graphNodes;
    }

    private function buildHistoryCommand(int $limit, int $skip, string $scope): string
    {
        $command = "log --graph --date-order --decorate=short --format='%x1e%H%x1f%P%x1f%an%x1f%ar%x1f%s%x1f%D' -n {$limit} --skip {$skip}";

        if ($scope === 'all') {
            $command .= ' --all';
        }

        return $command;
    }

    /**
     * @return array<HistoryRow>
     */
    private function parseHistoryRows(string $output): array
    {
        if (trim($output) === '') {
            return [];
        }

        $lines = explode("\n", rtrim($output, "\n"));
        $entries = [];
        $currentEntry = null;

        foreach ($lines as $line) {
            if ($line === '') {
                continue;
            }

            if (str_contains($line, "\x1e")) {
                if ($currentEntry !== null) {
                    $entries[] = $currentEntry;
                }

                [$graphPrefix, $payload] = explode("\x1e", $line, 2);
                $currentEntry = [
                    'graphPrefix' => $graphPrefix,
                    'payload' => $payload,
                    'continuationPrefixes' => [],
                ];

                continue;
            }

            if ($currentEntry !== null) {
                $currentEntry['continuationPrefixes'][] = $line;
            }
        }

        if ($currentEntry !== null) {
            $entries[] = $currentEntry;
        }

        $rows = [];
        foreach ($entries as $entry) {
            $historyRow = $this->parseHistoryRow($entry);
            if ($historyRow !== null) {
                $rows[] = $historyRow;
            }
        }

        return $rows;
    }

    private function parseHistoryRow(array $entry): ?HistoryRow
    {
        $fields = explode("\x1f", (string) ($entry['payload'] ?? ''));
        $sha = trim((string) ($fields[0] ?? ''));

        if ($sha === '') {
            return null;
        }

        $parentString = trim((string) ($fields[1] ?? ''));
        $parents = $parentString === ''
            ? []
            : array_values(array_filter(explode(' ', $parentString)));

        $refString = trim((string) ($fields[5] ?? ''));
        $refs = [];
        if ($refString !== '') {
            $refs = array_values(array_filter(array_map('trim', explode(',', $refString))));
        }

        $graphCells = $this->parseGraphCells((string) ($entry['graphPrefix'] ?? ''));
        $continuationCells = [];
        foreach ($entry['continuationPrefixes'] ?? [] as $prefix) {
            $continuationCells[] = $this->parseGraphCells((string) $prefix);
        }

        return new HistoryRow(
            sha: $sha,
            shortSha: substr($sha, 0, 7),
            parents: $parents,
            refs: $refs,
            message: trim((string) ($fields[4] ?? '')),
            author: trim((string) ($fields[2] ?? '')),
            date: trim((string) ($fields[3] ?? '')),
            graphCells: $graphCells,
            continuationCells: $continuationCells,
            hasGraphData: ! empty($graphCells),
        );
    }

    /**
     * @return array<string>
     */
    private function parseGraphCells(string $prefix): array
    {
        $chars = preg_split('//u', $prefix, -1, PREG_SPLIT_NO_EMPTY);
        if (! is_array($chars)) {
            return [];
        }

        $cells = [];
        foreach ($chars as $char) {
            $cells[] = match ($char) {
                '*', '|', '/', '\\', '_', ' ' => $char,
                default => ' ',
            };
        }

        while (! empty($cells) && end($cells) === ' ') {
            array_pop($cells);
        }

        return $cells;
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
}
