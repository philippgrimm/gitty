<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('commit stores message in history', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        "git commit -m 'feat: add new feature'" => Process::result('[main 1234567890abcdef1234567890abcdef12345678] feat: add new feature'),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $settingsService = app(SettingsService::class);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add new feature')
        ->call('commit');

    $history = $settingsService->getCommitHistory($this->testRepoPath);
    expect($history)->toContain('feat: add new feature');
});

test('cycling up loads previous message', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $settingsService = app(SettingsService::class);
    $settingsService->addCommitMessage($this->testRepoPath, 'feat: first commit');
    $settingsService->addCommitMessage($this->testRepoPath, 'fix: second commit');

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'current draft');

    $component->call('cycleHistory', 'up')
        ->assertSet('message', 'fix: second commit')
        ->assertSet('historyIndex', 0)
        ->assertSet('draftMessage', 'current draft');

    $component->call('cycleHistory', 'up')
        ->assertSet('message', 'feat: first commit')
        ->assertSet('historyIndex', 1);
});

test('cycling down returns to draft', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $settingsService = app(SettingsService::class);
    $settingsService->addCommitMessage($this->testRepoPath, 'feat: first commit');
    $settingsService->addCommitMessage($this->testRepoPath, 'fix: second commit');

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'current draft');

    $component->call('cycleHistory', 'up')
        ->assertSet('message', 'fix: second commit')
        ->assertSet('historyIndex', 0);

    $component->call('cycleHistory', 'down')
        ->assertSet('message', 'current draft')
        ->assertSet('historyIndex', -1);
});

test('history limited to 20 messages', function () {
    $settingsService = app(SettingsService::class);

    // Add 25 messages
    for ($i = 1; $i <= 25; $i++) {
        $settingsService->addCommitMessage($this->testRepoPath, "commit message {$i}");
    }

    $history = $settingsService->getCommitHistory($this->testRepoPath);

    expect($history)->toHaveCount(20)
        ->and($history[0])->toBe('commit message 25')
        ->and($history[19])->toBe('commit message 6');
});

test('duplicate messages deduplicated', function () {
    $settingsService = app(SettingsService::class);

    $settingsService->addCommitMessage($this->testRepoPath, 'feat: add feature');
    $settingsService->addCommitMessage($this->testRepoPath, 'fix: bug fix');
    $settingsService->addCommitMessage($this->testRepoPath, 'feat: add feature');

    $history = $settingsService->getCommitHistory($this->testRepoPath);

    expect($history)->toHaveCount(2)
        ->and($history[0])->toBe('feat: add feature')
        ->and($history[1])->toBe('fix: bug fix');
});

test('selecting from dropdown fills textarea', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $settingsService = app(SettingsService::class);
    $settingsService->addCommitMessage($this->testRepoPath, 'feat: selected message');

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('selectHistoryMessage', 'feat: selected message')
        ->assertSet('message', 'feat: selected message')
        ->assertSet('historyIndex', -1);
});

test('commit and push stores message in history', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        "git commit -m 'feat: add feature'" => Process::result('[main 1234567890abcdef1234567890abcdef12345678] feat: add feature'),
        'git push' => Process::result(''),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $settingsService = app(SettingsService::class);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add feature')
        ->call('commitAndPush');

    $history = $settingsService->getCommitHistory($this->testRepoPath);
    expect($history)->toContain('feat: add feature');
});

test('history index resets after commit', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        "git commit -m 'feat: new commit'" => Process::result('[main 1234567890abcdef1234567890abcdef12345678] feat: new commit'),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $settingsService = app(SettingsService::class);
    $settingsService->addCommitMessage($this->testRepoPath, 'feat: old commit');

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);

    $component->call('cycleHistory', 'up')
        ->assertSet('historyIndex', 0);

    $component->set('message', 'feat: new commit')
        ->call('commit')
        ->assertSet('historyIndex', -1);
});

test('stored history loads on mount', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    $settingsService = app(SettingsService::class);
    $settingsService->addCommitMessage($this->testRepoPath, 'feat: stored message 1');
    $settingsService->addCommitMessage($this->testRepoPath, 'fix: stored message 2');

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);

    expect($component->get('storedHistory'))->toHaveCount(2)
        ->and($component->get('storedHistory')[0])->toBe('fix: stored message 2')
        ->and($component->get('storedHistory')[1])->toBe('feat: stored message 1');
});
