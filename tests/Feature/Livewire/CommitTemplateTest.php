<?php

declare(strict_types=1);

use App\Livewire\CommitPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('getTemplates returns conventional commit types', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git config --get commit.template' => Process::result('', exitCode: 1),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);
    $templates = $component->invade()->getTemplates();

    expect($templates)->toHaveCount(10)
        ->and($templates[0]['type'])->toBe('feat')
        ->and($templates[0]['prefix'])->toBe('feat: ')
        ->and($templates[1]['type'])->toBe('fix')
        ->and($templates[1]['prefix'])->toBe('fix: ');
});

test('applyTemplate sets message with prefix', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('applyTemplate', 'feat: ')
        ->assertSet('message', 'feat: ');
});

test('applyTemplate prepends to existing message without type', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'add login page')
        ->call('applyTemplate', 'feat: ')
        ->assertSet('message', 'feat: add login page');
});

test('applyTemplate does not double-prefix typed message', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('message', 'feat: add login page')
        ->call('applyTemplate', 'fix: ')
        ->assertSet('message', 'feat: add login page');
});

test('applyTemplate replaces prefill with template', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusOnFeatureBranch()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('message', 'feat(JIRA-123): ')
        ->call('applyTemplate', 'fix: ')
        ->assertSet('message', 'fix: ');
});

test('loadCustomTemplate reads from repo .gitmessage file', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    // Create a .gitmessage file in the test repo
    file_put_contents($this->testRepoPath.'/.gitmessage', "feat(CUSTOM): \n\n# Custom template from repo");

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);
    $templates = $component->invade()->getTemplates();

    expect($templates)->toHaveCount(11)
        ->and($templates[0]['type'])->toBe('custom')
        ->and($templates[0]['label'])->toBe('Custom Template')
        ->and($templates[0]['prefix'])->toContain('feat(CUSTOM):');

    // Cleanup
    unlink($this->testRepoPath.'/.gitmessage');
});

test('loadCustomTemplate reads from git config commit.template', function () {
    $customTemplatePath = '/tmp/gitty-custom-template';
    file_put_contents($customTemplatePath, "chore: \n\n# Custom template from config");

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
        'git config --get commit.template' => Process::result($customTemplatePath),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);
    $templates = $component->invade()->getTemplates();

    expect($templates)->toHaveCount(11)
        ->and($templates[0]['type'])->toBe('custom')
        ->and($templates[0]['prefix'])->toContain('chore:');

    // Cleanup
    unlink($customTemplatePath);
});

test('getTemplates returns only conventional commits when no custom template exists', function () {
    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusWithStagedChanges()),
        'git log --oneline -n 10' => Process::result(GitOutputFixtures::logOneline()),
        'git config --get commit.template' => Process::result('', exitCode: 1),
    ]);

    $component = Livewire::test(CommitPanel::class, ['repoPath' => $this->testRepoPath]);
    $templates = $component->invade()->getTemplates();

    expect($templates)->toHaveCount(10)
        ->and($templates[0]['type'])->toBe('feat')
        ->and($templates[9]['type'])->toBe('build');
});
