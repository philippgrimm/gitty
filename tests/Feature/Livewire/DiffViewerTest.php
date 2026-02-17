<?php

declare(strict_types=1);

use App\Livewire\DiffViewer;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

it('mounts with empty state', function () {
    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSet('file', null)
        ->assertSet('isStaged', false)
        ->assertSet('isEmpty', true)
        ->assertSet('isBinary', false)
        ->assertSee('No file selected');
});

it('loads diff for unstaged file', function () {
    Process::fake([
        "git diff -- 'README.md'" => GitOutputFixtures::diffUnstaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false)
        ->assertSet('file', 'README.md')
        ->assertSet('isStaged', false)
        ->assertSet('isEmpty', false)
        ->assertSee('README.md')
        ->assertSee('+3')
        ->assertSee('-1');

    Process::assertRan("git diff -- 'README.md'");
});

it('loads diff for staged file', function () {
    Process::fake([
        "git diff --cached -- 'src/App.php'" => GitOutputFixtures::diffStaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'src/App.php', true)
        ->assertSet('file', 'src/App.php')
        ->assertSet('isStaged', true)
        ->assertSet('isEmpty', false)
        ->assertSee('src/App.php')
        ->assertSee('+1')
        ->assertSee('-1');

    Process::assertRan("git diff --cached -- 'src/App.php'");
});

it('handles empty diff', function () {
    Process::fake([
        "git diff -- 'empty.txt'" => '',
        "git status --porcelain=v2 -- 'empty.txt'" => '',
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'empty.txt', false)
        ->assertSet('isEmpty', true)
        ->assertSee('No changes to display');
});

it('loads diff for untracked file', function () {
    Process::fake([
        "git diff -- 'new-file.txt'" => '',
        "git status --porcelain=v2 -- 'new-file.txt'" => GitOutputFixtures::statusWithSingleUntrackedFile(),
        "git diff --no-index -- '/dev/null' 'new-file.txt'" => GitOutputFixtures::diffUntracked(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'new-file.txt', false)
        ->assertSet('file', 'new-file.txt')
        ->assertSet('isStaged', false)
        ->assertSet('isEmpty', false)
        ->assertSee('new-file.txt')
        ->assertSee('ADDED')
        ->assertSee('+2');

    Process::assertRan("git diff --no-index -- '/dev/null' 'new-file.txt'");
});

it('handles image file as image diff', function () {
    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'image.png', false)
        ->assertSet('isImage', true)
        ->assertSet('isBinary', false);
});

it('listens to file-selected event', function () {
    Process::fake([
        "git diff -- 'README.md'" => GitOutputFixtures::diffUnstaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('file-selected', file: 'README.md', staged: false)
        ->assertSet('file', 'README.md')
        ->assertSet('isStaged', false)
        ->assertSee('README.md');

    Process::assertRan("git diff -- 'README.md'");
});

it('displays status badge for modified file', function () {
    Process::fake([
        "git diff -- 'README.md'" => GitOutputFixtures::diffUnstaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false)
        ->assertSee('MODIFIED');
});

it('renders diff html with syntax highlighting', function () {
    Process::fake([
        "git diff --cached -- 'src/App.php'" => GitOutputFixtures::diffStaged(),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'src/App.php', true)
        ->assertSet('isEmpty', false)
        ->assertSet('isBinary', false);

    $files = $component->get('files');
    expect($files)->not->toBeEmpty();
    expect($files[0])->toHaveKey('language');
    expect($files[0])->toHaveKey('hunks');

    $component->assertSee('Hello, Gitty!');
});

it('stores parsed diff data with hunks for staging operations', function () {
    Process::fake([
        "git diff -- 'README.md'" => GitOutputFixtures::diffUnstaged(),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false);

    $files = $component->get('files');
    expect($files)->toBeArray();
    expect($files)->toHaveCount(1);
    expect($files[0])->toHaveKey('hunks');
    expect($files[0]['hunks'])->toBeArray();
    expect($files[0]['hunks'])->not->toBeEmpty();
    expect($files[0]['hunks'][0])->toHaveKeys(['oldStart', 'oldCount', 'newStart', 'newCount', 'header', 'lines']);
});

it('stages a hunk from unstaged diff', function () {
    Process::fake([
        "git diff -- 'README.md'" => GitOutputFixtures::diffUnstaged(),
        'git apply --cached' => Process::result(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false)
        ->call('stageHunk', 0, 0)
        ->assertDispatched('refresh-staging');

    Process::assertRan('git apply --cached');
});

it('unstages a hunk from staged diff', function () {
    Process::fake([
        "git diff --cached -- 'src/App.php'" => GitOutputFixtures::diffStaged(),
        'git apply --cached --reverse' => Process::result(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'src/App.php', true)
        ->call('unstageHunk', 0, 0)
        ->assertDispatched('refresh-staging');

    Process::assertRan('git apply --cached --reverse');
});

it('reloads diff after staging a hunk', function () {
    Process::fake([
        "git diff -- 'README.md'" => GitOutputFixtures::diffUnstaged(),
        'git apply --cached' => Process::result(),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false)
        ->call('stageHunk', 0, 0);

    // Should have called git diff twice: once for initial load, once after staging
    Process::assertRan("git diff -- 'README.md'", 2);
});

it('renders stage button for unstaged diff', function () {
    Process::fake([
        "git diff -- 'README.md'" => GitOutputFixtures::diffUnstaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false)
        ->assertSee('+ Stage')
        ->assertDontSee('− Unstage');
});

it('renders unstage button for staged diff', function () {
    Process::fake([
        "git diff --cached -- 'src/App.php'" => GitOutputFixtures::diffStaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'src/App.php', true)
        ->assertSee('− Unstage')
        ->assertDontSee('+ Stage');
});
