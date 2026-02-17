<?php

declare(strict_types=1);

use App\Livewire\HistoryPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('promptReset sets modal properties and shows modal', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
        'git branch -r --contains abc123' => Process::result(''),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('promptReset', 'abc123', 'Initial commit')
        ->assertSet('resetTargetSha', 'abc123')
        ->assertSet('resetTargetMessage', 'Initial commit')
        ->assertSet('resetMode', 'soft')
        ->assertSet('hardResetConfirmText', '')
        ->assertSet('showResetModal', true);
});

test('promptRevert sets modal properties and shows modal', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('promptRevert', 'abc123', 'Initial commit')
        ->assertSet('resetTargetSha', 'abc123')
        ->assertSet('resetTargetMessage', 'Initial commit')
        ->assertSet('showRevertModal', true);
});

test('confirmReset with soft mode calls resetSoft', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
        'git reset --soft abc123' => Process::result(''),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('resetTargetSha', 'abc123')
        ->set('resetMode', 'soft')
        ->call('confirmReset')
        ->assertSet('showResetModal', false)
        ->assertDispatched('status-updated')
        ->assertDispatched('refresh-staging');

    Process::assertRan('git reset --soft abc123');
});

test('confirmReset with mixed mode calls resetMixed', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
        'git reset abc123' => Process::result(''),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('resetTargetSha', 'abc123')
        ->set('resetMode', 'mixed')
        ->call('confirmReset')
        ->assertSet('showResetModal', false)
        ->assertDispatched('status-updated');

    Process::assertRan('git reset abc123');
});

test('confirmReset with hard mode calls resetHard when DISCARD is typed', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
        'git reset --hard abc123' => Process::result(''),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('resetTargetSha', 'abc123')
        ->set('resetMode', 'hard')
        ->set('hardResetConfirmText', 'DISCARD')
        ->call('confirmReset')
        ->assertSet('showResetModal', false)
        ->assertDispatched('status-updated');

    Process::assertRan('git reset --hard abc123');
});

test('confirmReset with hard mode blocked without typing DISCARD', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('resetTargetSha', 'abc123')
        ->set('resetMode', 'hard')
        ->set('hardResetConfirmText', 'discard')
        ->call('confirmReset')
        ->assertDispatched('show-error');

    Process::assertNotRan('git reset --hard abc123');
});

test('confirmRevert calls revertCommit', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
        'git revert abc123 --no-edit' => Process::result(''),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('resetTargetSha', 'abc123')
        ->call('confirmRevert')
        ->assertSet('showRevertModal', false)
        ->assertDispatched('status-updated');

    Process::assertRan('git revert abc123 --no-edit');
});

test('confirmRevert dispatches error on conflict', function () {
    Process::fake([
        'git log --pretty=format:"%H|%h|%an|%ae|%ar|%s|%D" -100' => Process::result(GitOutputFixtures::logOneline()),
        'git revert abc123 --no-edit' => Process::result(
            output: '',
            errorOutput: 'error: could not revert abc123... conflict in file.txt',
            exitCode: 1
        ),
    ]);

    Livewire::test(HistoryPanel::class, ['repoPath' => $this->testRepoPath])
        ->set('resetTargetSha', 'abc123')
        ->call('confirmRevert')
        ->assertSet('showRevertModal', false)
        ->assertDispatched('show-error');
});
