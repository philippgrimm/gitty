<?php

declare(strict_types=1);

use App\Livewire\DiffViewer;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->repoPath = base_path('tests/fixtures/test-repo');
});

test('default view mode is unified', function (): void {
    Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath])
        ->assertSet('diffViewMode', 'unified');
});

test('toggleDiffViewMode switches to split', function (): void {
    Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath])
        ->assertSet('diffViewMode', 'unified')
        ->call('toggleDiffViewMode')
        ->assertSet('diffViewMode', 'split');
});

test('toggleDiffViewMode switches back to unified', function (): void {
    Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath])
        ->set('diffViewMode', 'split')
        ->call('toggleDiffViewMode')
        ->assertSet('diffViewMode', 'unified');
});

test('getSplitLines pairs context lines correctly', function (): void {
    $component = new DiffViewer;
    $component->repoPath = $this->repoPath;

    $hunk = [
        'lines' => [
            ['type' => 'context', 'content' => 'line 1', 'oldLineNumber' => 1, 'newLineNumber' => 1],
            ['type' => 'context', 'content' => 'line 2', 'oldLineNumber' => 2, 'newLineNumber' => 2],
            ['type' => 'context', 'content' => 'line 3', 'oldLineNumber' => 3, 'newLineNumber' => 3],
        ],
    ];

    $pairs = $component->getSplitLines($hunk);

    expect($pairs)->toHaveCount(3);
    expect($pairs[0]['left'])->toBe($hunk['lines'][0]);
    expect($pairs[0]['right'])->toBe($hunk['lines'][0]);
    expect($pairs[1]['left'])->toBe($hunk['lines'][1]);
    expect($pairs[1]['right'])->toBe($hunk['lines'][1]);
    expect($pairs[2]['left'])->toBe($hunk['lines'][2]);
    expect($pairs[2]['right'])->toBe($hunk['lines'][2]);
});

test('getSplitLines pairs deletion and addition blocks', function (): void {
    $component = new DiffViewer;
    $component->repoPath = $this->repoPath;

    $hunk = [
        'lines' => [
            ['type' => 'context', 'content' => 'line 1', 'oldLineNumber' => 1, 'newLineNumber' => 1],
            ['type' => 'deletion', 'content' => 'old line 2', 'oldLineNumber' => 2, 'newLineNumber' => null],
            ['type' => 'deletion', 'content' => 'old line 3', 'oldLineNumber' => 3, 'newLineNumber' => null],
            ['type' => 'addition', 'content' => 'new line 2', 'oldLineNumber' => null, 'newLineNumber' => 2],
            ['type' => 'addition', 'content' => 'new line 3', 'oldLineNumber' => null, 'newLineNumber' => 3],
            ['type' => 'context', 'content' => 'line 4', 'oldLineNumber' => 4, 'newLineNumber' => 4],
        ],
    ];

    $pairs = $component->getSplitLines($hunk);

    expect($pairs)->toHaveCount(4);

    // First context line
    expect($pairs[0]['left'])->toBe($hunk['lines'][0]);
    expect($pairs[0]['right'])->toBe($hunk['lines'][0]);

    // First deletion-addition pair
    expect($pairs[1]['left'])->toBe($hunk['lines'][1]);
    expect($pairs[1]['right'])->toBe($hunk['lines'][3]);

    // Second deletion-addition pair
    expect($pairs[2]['left'])->toBe($hunk['lines'][2]);
    expect($pairs[2]['right'])->toBe($hunk['lines'][4]);

    // Last context line
    expect($pairs[3]['left'])->toBe($hunk['lines'][5]);
    expect($pairs[3]['right'])->toBe($hunk['lines'][5]);
});

test('getSplitLines handles unequal deletion and addition counts', function (): void {
    $component = new DiffViewer;
    $component->repoPath = $this->repoPath;

    $hunk = [
        'lines' => [
            ['type' => 'deletion', 'content' => 'old line 1', 'oldLineNumber' => 1, 'newLineNumber' => null],
            ['type' => 'deletion', 'content' => 'old line 2', 'oldLineNumber' => 2, 'newLineNumber' => null],
            ['type' => 'deletion', 'content' => 'old line 3', 'oldLineNumber' => 3, 'newLineNumber' => null],
            ['type' => 'addition', 'content' => 'new line 1', 'oldLineNumber' => null, 'newLineNumber' => 1],
        ],
    ];

    $pairs = $component->getSplitLines($hunk);

    expect($pairs)->toHaveCount(3);

    // First three pairs: deletions paired with addition (first) and nulls
    expect($pairs[0]['left'])->toBe($hunk['lines'][0]);
    expect($pairs[0]['right'])->toBe($hunk['lines'][3]);

    expect($pairs[1]['left'])->toBe($hunk['lines'][1]);
    expect($pairs[1]['right'])->toBeNull();

    expect($pairs[2]['left'])->toBe($hunk['lines'][2]);
    expect($pairs[2]['right'])->toBeNull();
});

test('component renders in split mode without errors', function (): void {
    Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath])
        ->set('diffViewMode', 'split')
        ->assertSet('diffViewMode', 'split')
        ->assertOk();
});

test('palette event toggles diff view mode', function (): void {
    Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath])
        ->assertSet('diffViewMode', 'unified')
        ->dispatch('palette-toggle-diff-view')
        ->assertSet('diffViewMode', 'split')
        ->dispatch('palette-toggle-diff-view')
        ->assertSet('diffViewMode', 'unified');
});
