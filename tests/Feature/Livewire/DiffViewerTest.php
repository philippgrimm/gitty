<?php

declare(strict_types=1);

use App\Livewire\DiffViewer;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath . '/.git')) {
        mkdir($this->testRepoPath . '/.git', 0755, true);
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
        'git diff -- README.md' => GitOutputFixtures::diffUnstaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false)
        ->assertSet('file', 'README.md')
        ->assertSet('isStaged', false)
        ->assertSet('isEmpty', false)
        ->assertSee('README.md')
        ->assertSee('+3')
        ->assertSee('-1');

    Process::assertRan('git diff -- README.md');
});

it('loads diff for staged file', function () {
    Process::fake([
        'git diff --cached -- src/App.php' => GitOutputFixtures::diffStaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'src/App.php', true)
        ->assertSet('file', 'src/App.php')
        ->assertSet('isStaged', true)
        ->assertSet('isEmpty', false)
        ->assertSee('src/App.php')
        ->assertSee('+1')
        ->assertSee('-1');

    Process::assertRan('git diff --cached -- src/App.php');
});

it('handles empty diff', function () {
    Process::fake([
        'git diff -- empty.txt' => '',
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'empty.txt', false)
        ->assertSet('isEmpty', true)
        ->assertSee('No changes to display');
});

it('handles binary file', function () {
    $binaryDiff = <<<'OUTPUT'
diff --git a/image.png b/image.png
index b2c3d4e..f6a7b8c 100644
Binary files a/image.png and b/image.png differ

OUTPUT;

    Process::fake([
        'git diff -- image.png' => $binaryDiff,
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'image.png', false)
        ->assertSet('isBinary', true)
        ->assertSee('Binary file')
        ->assertSee('cannot display diff');
});

it('listens to file-selected event', function () {
    Process::fake([
        'git diff -- README.md' => GitOutputFixtures::diffUnstaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->dispatch('file-selected', file: 'README.md', staged: false)
        ->assertSet('file', 'README.md')
        ->assertSet('isStaged', false)
        ->assertSee('README.md');

    Process::assertRan('git diff -- README.md');
});

it('displays status badge for modified file', function () {
    Process::fake([
        'git diff -- README.md' => GitOutputFixtures::diffUnstaged(),
    ]);

    Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'README.md', false)
        ->assertSee('MODIFIED');
});

it('renders diff html with syntax highlighting', function () {
    Process::fake([
        'git diff --cached -- src/App.php' => GitOutputFixtures::diffStaged(),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->testRepoPath])
        ->call('loadDiff', 'src/App.php', true)
        ->assertSet('isEmpty', false)
        ->assertSet('isBinary', false);

    expect($component->get('renderedHtml'))->not->toBeEmpty();
    
    $component->assertSee('Hello, Gitty!');
});
