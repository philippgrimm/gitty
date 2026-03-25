<?php

declare(strict_types=1);

namespace App\Livewire;

use App\DTOs\HistoryRow;
use App\Services\Git\CommitService;
use App\Services\Git\GitService;
use App\Services\Git\GraphService;
use App\Services\Git\ResetService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;

class HistoryPanel extends Component
{
    public string $repoPath;

    public int $commitsCount = 0;

    public int $page = 1;

    public int $perPage = 100;

    public bool $hasMore = false;

    public ?string $selectedCommitSha = null;

    public bool $showGraph = true;

    public string $historyScope = 'current';

    public int $graphColumns = 1;

    public bool $showResetModal = false;

    public bool $showRevertModal = false;

    public bool $showCherryPickModal = false;

    public ?string $resetTargetSha = null;

    public ?string $resetTargetMessage = null;

    public ?string $cherryPickTargetSha = null;

    public ?string $cherryPickTargetMessage = null;

    public string $resetMode = 'soft';

    public string $hardResetConfirmText = '';

    public bool $targetCommitPushed = false;

    public int $rebaseCommitCount = 5;

    /**
     * @var array<int, array{
     *     sha: string,
     *     shortSha: string,
     *     parents: array<string>,
     *     refs: array<string>,
     *     message: string,
     *     author: string,
     *     date: string,
     *     graphCells: array<string>,
     *     continuationCells: array<array<string>>,
     *     hasGraphData: bool
     * }>
     */
    public array $historyRows = [];

    public bool $loaded = false;

    private int $maxAutoLoadPages = 10;

    public function mount(string $repoPath): void
    {
        $this->repoPath = $repoPath;
        $this->historyRows = [];

        if (config('app.debug')) {
            Log::debug('[perf] HistoryPanel::mount() (deferred)');
        }
    }

    #[On('activate-history')]
    public function activate(): void
    {
        if ($this->loaded) {
            return;
        }

        $t = microtime(true);
        $this->loaded = true;
        $this->loadHistory();

        if (config('app.debug')) {
            Log::debug(sprintf('[perf] HistoryPanel::activate() %.1fms', (microtime(true) - $t) * 1000));
        }
    }

    private function loadHistory(bool $append = false): void
    {
        try {
            $graphService = new GraphService($this->repoPath);
            $skip = max(0, ($this->page - 1) * $this->perPage);
            $rows = collect($graphService->getHistoryRows(
                limit: $this->perPage + 1,
                skip: $skip,
                scope: $this->historyScope,
            ));

            $this->hasMore = $rows->count() > $this->perPage;
            $pageRows = $rows
                ->take($this->perPage)
                ->map(fn (HistoryRow $row): array => $this->historyRowToArray($row))
                ->values()
                ->all();

            if ($append) {
                $this->historyRows = array_values(array_merge($this->historyRows, $pageRows));
            } else {
                $this->historyRows = $pageRows;
            }

            $this->commitsCount = count($this->historyRows);
            $this->graphColumns = $this->calculateGraphColumns(collect($this->historyRows));
        } catch (\Exception) {
            $this->historyRows = [];
            $this->hasMore = false;
            $this->commitsCount = 0;
            $this->graphColumns = 1;
        }
    }

    public function loadMore(): void
    {
        if (! $this->hasMore) {
            return;
        }

        $this->page++;
        $this->loadHistory(append: true);
    }

    public function setHistoryScope(string $scope): void
    {
        if (! in_array($scope, ['current', 'all'], true)) {
            return;
        }

        if ($this->historyScope === $scope) {
            return;
        }

        $this->historyScope = $scope;
        $this->page = 1;
        $this->selectedCommitSha = null;
        $this->historyRows = [];
        $this->loadHistory();
    }

    public function selectCommit(string $sha): void
    {
        $this->selectedCommitSha = $sha;
        $this->dispatch('commit-selected', sha: $sha);
    }

    #[On('commit-selected')]
    public function handleExternalCommitSelected(string $sha): void
    {
        if ($sha === '') {
            return;
        }

        $found = $this->ensureCommitIsLoaded($sha);

        if (! $found && $this->historyScope === 'current') {
            $this->historyScope = 'all';
            $this->page = 1;
            $this->selectedCommitSha = null;
            $this->historyRows = [];
            $this->loadHistory();
            $found = $this->ensureCommitIsLoaded($sha);
        }

        if (! $found) {
            $this->dispatch('show-error', message: 'Commit not found in history.', type: 'info');

            return;
        }

        $this->selectedCommitSha = $sha;
        $this->dispatch('scroll-history-to-commit', sha: $sha);
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
        $this->page = 1;
        $this->selectedCommitSha = null;
        $this->historyScope = 'current';
        $this->historyRows = [];
        $this->loadHistory();
    }

    #[On('status-updated')]
    public function handleStatusUpdated(): void
    {
        $this->refreshHistoryList();
    }

    private function refreshHistoryList(): void
    {
        $this->page = 1;
        $this->selectedCommitSha = null;
        $this->loadHistory();
    }

    public function promptReset(string $sha, string $message): void
    {
        $this->resetTargetSha = $sha;
        $this->resetTargetMessage = $message;
        $this->resetMode = 'soft';
        $this->hardResetConfirmText = '';
        $this->targetCommitPushed = $this->isCommitPushed($sha);
        $this->showResetModal = true;
    }

    public function confirmReset(): void
    {
        if ($this->resetMode === 'hard' && $this->hardResetConfirmText !== 'DISCARD') {
            $this->dispatch('show-error', message: 'Type "DISCARD" to confirm hard reset');

            return;
        }

        try {
            $resetService = new ResetService($this->repoPath);

            match ($this->resetMode) {
                'soft' => $resetService->resetSoft($this->resetTargetSha),
                'mixed' => $resetService->resetMixed($this->resetTargetSha),
                'hard' => $resetService->resetHard($this->resetTargetSha),
            };

            $this->showResetModal = false;
            $this->dispatch('status-updated');
            $this->dispatch('refresh-staging');
            $this->dispatch('show-success', message: 'Reset to commit '.substr($this->resetTargetSha, 0, 8));
        } catch (\Exception $e) {
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    public function promptRevert(string $sha, string $message): void
    {
        $this->resetTargetSha = $sha;
        $this->resetTargetMessage = $message;
        $this->showRevertModal = true;
    }

    public function confirmRevert(): void
    {
        try {
            $resetService = new ResetService($this->repoPath);
            $resetService->revertCommit($this->resetTargetSha);

            $this->showRevertModal = false;
            $this->dispatch('status-updated');
            $this->dispatch('show-success', message: 'Reverted commit '.substr($this->resetTargetSha, 0, 8));
        } catch (\Exception $e) {
            $this->showRevertModal = false;
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    private function isCommitPushed(string $sha): bool
    {
        try {
            $gitService = new GitService($this->repoPath);
            $status = $gitService->status();

            if (empty($status->upstream)) {
                return false;
            }

            $branchService = new \App\Services\Git\BranchService($this->repoPath);

            return $branchService->isCommitOnRemote($sha);
        } catch (\Exception) {
            return false;
        }
    }

    public function promptCherryPick(string $sha, string $message): void
    {
        $this->cherryPickTargetSha = $sha;
        $this->cherryPickTargetMessage = $message;
        $this->showCherryPickModal = true;
    }

    public function confirmCherryPick(): void
    {
        try {
            $commitService = new CommitService($this->repoPath);
            $result = $commitService->cherryPick($this->cherryPickTargetSha);

            if ($result->hasConflicts) {
                $this->showCherryPickModal = false;
                $this->dispatch('show-error', message: 'Cherry-pick failed: conflicts detected. Resolve conflicts and continue or abort.');

                return;
            }

            $this->showCherryPickModal = false;
            $this->dispatch('status-updated');
            $this->dispatch('show-success', message: 'Cherry-picked commit '.substr($this->cherryPickTargetSha, 0, 8));
        } catch (\Exception $e) {
            $this->showCherryPickModal = false;
            $this->dispatch('show-error', message: $e->getMessage());
        }
    }

    public function promptInteractiveRebase(string $sha): void
    {
        $this->dispatch('open-rebase-modal', ontoCommit: $sha, count: $this->rebaseCommitCount);
    }

    /**
     * @return array<string, array{message: string}>
     */
    private function buildCommitContextData(): array
    {
        $contextData = [];

        foreach ($this->historyRows as $row) {
            $sha = (string) ($row['sha'] ?? '');
            if ($sha === '') {
                continue;
            }

            $contextData[$sha] = ['message' => (string) ($row['message'] ?? '')];
        }

        return $contextData;
    }

    /**
     * @param  Collection<int, array{graphCells?: array<string>, continuationCells?: array<array<string>>}>  $rows
     */
    private function calculateGraphColumns(Collection $rows): int
    {
        $columns = 1;

        foreach ($rows as $row) {
            $columns = max($columns, count($row['graphCells'] ?? []));

            foreach ($row['continuationCells'] ?? [] as $continuationLine) {
                $columns = max($columns, count($continuationLine));
            }
        }

        return $columns;
    }

    private function ensureCommitIsLoaded(string $sha): bool
    {
        if ($this->containsCommit($sha)) {
            return true;
        }

        $autoLoadedPages = 0;

        while ($this->hasMore && $autoLoadedPages < $this->maxAutoLoadPages) {
            $this->page++;
            $this->loadHistory(append: true);
            $autoLoadedPages++;

            if ($this->containsCommit($sha)) {
                return true;
            }
        }

        return false;
    }

    private function containsCommit(string $sha): bool
    {
        foreach ($this->historyRows as $row) {
            if (($row['sha'] ?? null) === $sha) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, HistoryRow>
     */
    private function getHydratedHistoryRows(): Collection
    {
        return collect($this->historyRows)->map(
            fn (array $row): HistoryRow => $this->arrayToHistoryRow($row)
        );
    }

    /**
     * @return array{
     *     sha: string,
     *     shortSha: string,
     *     parents: array<string>,
     *     refs: array<string>,
     *     message: string,
     *     author: string,
     *     date: string,
     *     graphCells: array<string>,
     *     continuationCells: array<array<string>>,
     *     hasGraphData: bool
     * }
     */
    private function historyRowToArray(HistoryRow $row): array
    {
        return [
            'sha' => $row->sha,
            'shortSha' => $row->shortSha,
            'parents' => $row->parents,
            'refs' => $row->refs,
            'message' => $row->message,
            'author' => $row->author,
            'date' => $row->date,
            'graphCells' => $row->graphCells,
            'continuationCells' => $row->continuationCells,
            'hasGraphData' => $row->hasGraphData,
        ];
    }

    private function arrayToHistoryRow(array $row): HistoryRow
    {
        return new HistoryRow(
            sha: (string) ($row['sha'] ?? ''),
            shortSha: (string) ($row['shortSha'] ?? ''),
            parents: array_values(array_filter($row['parents'] ?? [], fn (mixed $parent): bool => is_string($parent))),
            refs: array_values(array_filter($row['refs'] ?? [], fn (mixed $ref): bool => is_string($ref))),
            message: (string) ($row['message'] ?? ''),
            author: (string) ($row['author'] ?? ''),
            date: (string) ($row['date'] ?? ''),
            graphCells: array_values(array_filter($row['graphCells'] ?? [], fn (mixed $cell): bool => is_string($cell))),
            continuationCells: collect($row['continuationCells'] ?? [])
                ->map(fn (mixed $line): array => array_values(array_filter(
                    is_array($line) ? $line : [],
                    fn (mixed $cell): bool => is_string($cell)
                )))
                ->values()
                ->all(),
            hasGraphData: (bool) ($row['hasGraphData'] ?? false),
        );
    }

    /**
     * Build SVG graph data from the history rows.
     *
     * Produces continuous SVG paths (with Bézier curves for lane transitions)
     * and circle elements for commit dots — rendered as a single overlay SVG
     * that spans the entire commit list.
     *
     * @param  Collection<int, HistoryRow>  $rows
     * @return array{
     *     paths: array<int, array{d: string, color: string}>,
     *     circles: array<int, array{cx: float, cy: float, r: float, fill: string, stroke: string, strokeWidth: float}>,
     *     width: int,
     *     height: int,
     *     rowHeight: int,
     * }
     */
    private function buildGraphSvgData(Collection $rows): array
    {
        $rowH = 36;
        $charW = 14;
        $offX = 12;
        $dotR = 3.5;
        $kappa = 0.5523;
        $laneColors = ['#4040B0', '#E05800', '#1E8C0A', '#0D9488', '#7C3AED', '#C2185B', '#0288D1', '#F57C00'];

        $rowCount = $rows->count();
        if ($rowCount === 0) {
            return ['paths' => [], 'circles' => [], 'width' => 0, 'height' => 0, 'rowHeight' => $rowH];
        }

        /** @var array<string, int> $shaIndex */
        $shaIndex = [];
        $rowsArray = $rows->values()->all();
        foreach ($rowsArray as $i => $row) {
            $shaIndex[$row->sha] = $i;
        }

        $maxCols = 1;
        foreach ($rowsArray as $row) {
            $maxCols = max($maxCols, count($row->graphCells));
            foreach ($row->continuationCells as $cont) {
                $maxCols = max($maxCols, count($cont));
            }
        }

        $svgW = $maxCols * $charW + $offX * 2;
        $svgH = $rowCount * $rowH;

        /** @var array<int, int> $commitCharPos */
        $commitCharPos = [];
        foreach ($rowsArray as $i => $row) {
            $starPos = array_search('*', $row->graphCells, true);
            $commitCharPos[$i] = is_int($starPos) ? $starPos : 0;
        }

        $charToX = fn (int $charIdx): float => $offX + $charIdx * $charW + $charW / 2.0;
        $rowCenterY = fn (int $rowIdx): float => $rowIdx * $rowH + $rowH / 2.0;
        $fmt = fn (float $v): string => rtrim(rtrim(number_format($v, 1, '.', ''), '0'), '.');

        /** @var array<string, array<string>> $pathSegmentsByColor */
        $pathSegmentsByColor = [];

        foreach ($rowsArray as $i => $row) {
            $childX = $charToX($commitCharPos[$i]);
            $childY = $rowCenterY($i);
            $childColor = $laneColors[$commitCharPos[$i] % 8];

            foreach ($row->parents as $parentSha) {
                if (! isset($shaIndex[$parentSha])) {
                    $fx = $fmt($childX);
                    $pathSegmentsByColor[$childColor][] = "M {$fx} {$fmt($childY)} L {$fx} {$svgH}";

                    continue;
                }

                $pi = $shaIndex[$parentSha];
                $parentX = $charToX($commitCharPos[$pi]);
                $parentY = $rowCenterY($pi);
                $parentColor = $laneColors[$commitCharPos[$pi] % 8];

                if (abs($childX - $parentX) < 0.01) {
                    $pathSegmentsByColor[$parentColor][] = "M {$fmt($childX)} {$fmt($childY)} L {$fmt($parentX)} {$fmt($parentY)}";
                } else {
                    // Quarter-circle arc — fork + merge pair forms a 180° semicircle
                    $branchLane = max($commitCharPos[$i], $commitCharPos[$pi]);
                    $color = $laneColors[$branchLane % 8];

                    $dx = $parentX - $childX;
                    $dy = $parentY - $childY;
                    $absDx = abs($dx);
                    $absDy = abs($dy);
                    $r = min($absDx, $absDy);

                    $d = "M {$fmt($childX)} {$fmt($childY)}";

                    if ($dx > 0) {
                        // Fork-out: horizontal from child → arc → vertical to parent
                        $arcStartX = $parentX - $r;
                        $arcEndY = $childY + $r;

                        if ($absDx > $r) {
                            $d .= " L {$fmt($arcStartX)} {$fmt($childY)}";
                        }
                        $d .= " C {$fmt($arcStartX + $r * $kappa)} {$fmt($childY)} {$fmt($parentX)} {$fmt($arcEndY - $r * $kappa)} {$fmt($parentX)} {$fmt($arcEndY)}";
                        if ($absDy > $r) {
                            $d .= " L {$fmt($parentX)} {$fmt($parentY)}";
                        }
                    } else {
                        // Merge-back: vertical on child lane → arc → horizontal to parent
                        $arcStartY = $parentY - $r;
                        $arcEndX = $childX - $r;

                        if ($absDy > $r) {
                            $d .= " L {$fmt($childX)} {$fmt($arcStartY)}";
                        }
                        $d .= " C {$fmt($childX)} {$fmt($arcStartY + $r * $kappa)} {$fmt($childX - $r * (1 - $kappa))} {$fmt($parentY)} {$fmt($arcEndX)} {$fmt($parentY)}";
                        if ($absDx > $r) {
                            $d .= " L {$fmt($parentX)} {$fmt($parentY)}";
                        }
                    }

                    $pathSegmentsByColor[$color][] = $d;
                }
            }
        }

        // Merge overlapping vertical segments per color to reduce SVG size
        foreach ($pathSegmentsByColor as $color => &$segments) {
            /** @var array<string, array<array{float, float}>> $verticals */
            $verticals = [];
            $other = [];

            foreach ($segments as $seg) {
                if (preg_match('/^M ([0-9.]+) ([0-9.]+) L \1 ([0-9.]+)$/', $seg, $m)) {
                    $verticals[$m[1]][] = [(float) $m[2], (float) $m[3]];
                } else {
                    $other[] = $seg;
                }
            }

            foreach ($verticals as $x => $ranges) {
                usort($ranges, fn (array $a, array $b): int => $a[0] <=> $b[0]);
                $merged = [$ranges[0]];
                for ($j = 1, $count = count($ranges); $j < $count; $j++) {
                    $last = &$merged[count($merged) - 1];
                    if ($ranges[$j][0] <= $last[1]) {
                        $last[1] = max($last[1], $ranges[$j][1]);
                    } else {
                        $merged[] = $ranges[$j];
                    }
                }
                foreach ($merged as $range) {
                    $other[] = "M {$x} {$fmt($range[0])} L {$x} {$fmt($range[1])}";
                }
            }

            $segments = $other;
        }
        unset($segments);

        $paths = [];
        foreach ($pathSegmentsByColor as $color => $segments) {
            if (! empty($segments)) {
                $laneIndex = array_search($color, $laneColors, true);
                $paths[] = [
                    'd' => implode(' ', $segments),
                    'color' => $color,
                    'laneIndex' => is_int($laneIndex) ? $laneIndex : 0,
                ];
            }
        }

        $circles = [];
        foreach ($rowsArray as $i => $row) {
            if (! $row->hasGraphData) {
                continue;
            }

            $cx = $charToX($commitCharPos[$i]);
            $cy = $rowCenterY($i);
            $color = $laneColors[$commitCharPos[$i] % 8];

            $isHead = false;
            foreach ($row->refs as $ref) {
                if (str_starts_with($ref, 'HEAD')) {
                    $isHead = true;
                    break;
                }
            }
            $isMerge = count($row->parents) > 1;

            $laneIdx = $commitCharPos[$i] % 8;

            if ($isHead) {
                $circles[] = [
                    'cx' => $cx,
                    'cy' => $cy,
                    'r' => $dotR + 1,
                    'fill' => '#ffffff',
                    'stroke' => $color,
                    'strokeWidth' => 2.0,
                    'laneIndex' => $laneIdx,
                    'type' => 'head',
                ];
            } elseif ($isMerge) {
                $circles[] = [
                    'cx' => $cx,
                    'cy' => $cy,
                    'r' => $dotR,
                    'fill' => '#ffffff',
                    'stroke' => $color,
                    'strokeWidth' => 2.0,
                    'laneIndex' => $laneIdx,
                    'type' => 'merge',
                ];
            } else {
                $circles[] = [
                    'cx' => $cx,
                    'cy' => $cy,
                    'r' => $dotR,
                    'fill' => $color,
                    'stroke' => '#ffffff',
                    'strokeWidth' => 1.5,
                    'laneIndex' => $laneIdx,
                    'type' => 'regular',
                ];
            }
        }

        return [
            'paths' => $paths,
            'circles' => $circles,
            'width' => (int) $svgW,
            'height' => (int) $svgH,
            'rowHeight' => $rowH,
        ];
    }

    public function render()
    {
        $rows = $this->getHydratedHistoryRows();

        return view('livewire.history-panel', [
            'rows' => $rows,
            'commitContextData' => $this->buildCommitContextData(),
            'graphColumns' => $this->graphColumns,
            'graphSvg' => $this->buildGraphSvgData($rows),
        ]);
    }
}
