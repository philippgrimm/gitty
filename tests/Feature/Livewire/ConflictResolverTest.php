<?php

declare(strict_types=1);

use App\Livewire\ConflictResolver;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('component mounts and checks merge state', function () {
    $mergeHeadPath = $this->testRepoPath.'/.git/MERGE_HEAD';
    file_put_contents($mergeHeadPath, 'abc123');

    Process::fake([
        'git status --porcelain=v2' => 'u UU N... 100644 100644 100644 000000 abc123 def456 ghi789 test.txt',
    ]);

    $component = Livewire::test(ConflictResolver::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('isInMergeState'))->toBeTrue()
        ->and($component->get('conflictFiles'))->toHaveCount(1);

    unlink($mergeHeadPath);
});

test('component shows empty state when not in merge', function () {
    $mergeHeadPath = $this->testRepoPath.'/.git/MERGE_HEAD';
    if (file_exists($mergeHeadPath)) {
        unlink($mergeHeadPath);
    }

    $component = Livewire::test(ConflictResolver::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('isInMergeState'))->toBeFalse()
        ->and($component->get('conflictFiles'))->toBeEmpty();
});

test('component selects file and loads conflict versions', function () {
    $mergeHeadPath = $this->testRepoPath.'/.git/MERGE_HEAD';
    file_put_contents($mergeHeadPath, 'abc123');

    Process::fake([
        'git status --porcelain=v2' => 'u UU N... 100644 100644 100644 000000 abc123 def456 ghi789 test.txt',
        'git show :1:"test.txt" 2>/dev/null' => "base content\n",
        'git show :2:"test.txt" 2>/dev/null' => "ours content\n",
        'git show :3:"test.txt" 2>/dev/null' => "theirs content\n",
        'git diff --numstat HEAD -- "test.txt" 2>/dev/null' => Process::result(output: '1	1	test.txt', exitCode: 0),
    ]);

    $component = Livewire::test(ConflictResolver::class, ['repoPath' => $this->testRepoPath])
        ->call('selectFile', 'test.txt');

    expect($component->get('selectedFile'))->toBe('test.txt')
        ->and($component->get('oursContent'))->toBe("ours content\n")
        ->and($component->get('theirsContent'))->toBe("theirs content\n")
        ->and($component->get('baseContent'))->toBe("base content\n")
        ->and($component->get('resultContent'))->toBe("ours content\n");

    unlink($mergeHeadPath);
});

test('component accepts ours sets result content', function () {
    $mergeHeadPath = $this->testRepoPath.'/.git/MERGE_HEAD';
    file_put_contents($mergeHeadPath, 'abc123');

    Process::fake([
        'git status --porcelain=v2' => 'u UU N... 100644 100644 100644 000000 abc123 def456 ghi789 test.txt',
        'git show :1:"test.txt" 2>/dev/null' => "base content\n",
        'git show :2:"test.txt" 2>/dev/null' => "ours content\n",
        'git show :3:"test.txt" 2>/dev/null' => "theirs content\n",
        'git diff --numstat HEAD -- "test.txt" 2>/dev/null' => Process::result(output: '1	1	test.txt', exitCode: 0),
    ]);

    $component = Livewire::test(ConflictResolver::class, ['repoPath' => $this->testRepoPath])
        ->call('selectFile', 'test.txt')
        ->call('acceptOurs');

    expect($component->get('resultContent'))->toBe("ours content\n");

    unlink($mergeHeadPath);
});

test('component accepts theirs sets result content', function () {
    $mergeHeadPath = $this->testRepoPath.'/.git/MERGE_HEAD';
    file_put_contents($mergeHeadPath, 'abc123');

    Process::fake([
        'git status --porcelain=v2' => 'u UU N... 100644 100644 100644 000000 abc123 def456 ghi789 test.txt',
        'git show :1:"test.txt" 2>/dev/null' => "base content\n",
        'git show :2:"test.txt" 2>/dev/null' => "ours content\n",
        'git show :3:"test.txt" 2>/dev/null' => "theirs content\n",
        'git diff --numstat HEAD -- "test.txt" 2>/dev/null' => Process::result(output: '1	1	test.txt', exitCode: 0),
    ]);

    $component = Livewire::test(ConflictResolver::class, ['repoPath' => $this->testRepoPath])
        ->call('selectFile', 'test.txt')
        ->call('acceptTheirs');

    expect($component->get('resultContent'))->toBe("theirs content\n");

    unlink($mergeHeadPath);
});

test('component accepts both concatenates content', function () {
    $mergeHeadPath = $this->testRepoPath.'/.git/MERGE_HEAD';
    file_put_contents($mergeHeadPath, 'abc123');

    Process::fake([
        'git status --porcelain=v2' => 'u UU N... 100644 100644 100644 000000 abc123 def456 ghi789 test.txt',
        'git show :1:"test.txt" 2>/dev/null' => "base content\n",
        'git show :2:"test.txt" 2>/dev/null' => "ours content\n",
        'git show :3:"test.txt" 2>/dev/null' => "theirs content\n",
        'git diff --numstat HEAD -- "test.txt" 2>/dev/null' => Process::result(output: '1	1	test.txt', exitCode: 0),
    ]);

    $component = Livewire::test(ConflictResolver::class, ['repoPath' => $this->testRepoPath])
        ->call('selectFile', 'test.txt')
        ->call('acceptBoth');

    expect($component->get('resultContent'))->toBe("ours content\n\ntheirs content\n");

    unlink($mergeHeadPath);
});
