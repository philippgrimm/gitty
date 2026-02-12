<?php

declare(strict_types=1);

use App\Livewire\SyncPanel;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;
use Tests\Mocks\GitOutputFixtures;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath . '/.git')) {
        mkdir($this->testRepoPath . '/.git', 0755, true);
    }
});

test('component mounts with repo path', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSet('isOperationRunning', false)
        ->assertSet('error', '');
});

test('push operation succeeds', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git push origin main' => Process::result("To github.com:user/repo.git\n   abc123..def456  main -> main"),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPush')
        ->assertSet('lastOperation', 'push')
        ->assertSet('error', '')
        ->assertDispatched('status-updated');

    Process::assertRan('git push origin main');
});

test('push operation fails with error message', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git push origin main' => Process::result('error: failed to push some refs', exitCode: 1),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPush')
        ->assertSet('error', 'error: failed to push some refs')
        ->assertNotDispatched('status-updated');
});

test('pull operation succeeds', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git pull origin main' => Process::result("From github.com:user/repo\n * branch            main       -> FETCH_HEAD\nUpdating abc123..def456\nFast-forward"),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPull')
        ->assertSet('lastOperation', 'pull')
        ->assertSet('error', '')
        ->assertDispatched('status-updated');

    Process::assertRan('git pull origin main');
});

test('pull operation fails with error message', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git pull origin main' => Process::result('error: Your local changes would be overwritten', exitCode: 1),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPull')
        ->assertSet('error', 'error: Your local changes would be overwritten')
        ->assertNotDispatched('status-updated');
});

test('fetch operation succeeds', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git fetch origin' => Process::result("From github.com:user/repo\n   abc123..def456  main       -> origin/main"),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncFetch')
        ->assertSet('lastOperation', 'fetch')
        ->assertSet('error', '')
        ->assertDispatched('status-updated');

    Process::assertRan('git fetch origin');
});

test('fetch all operation succeeds', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git fetch --all' => Process::result("Fetching origin\nFetching upstream"),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncFetchAll')
        ->assertSet('lastOperation', 'fetch-all')
        ->assertSet('error', '')
        ->assertDispatched('status-updated');

    Process::assertRan('git fetch --all');
});

test('force push with lease succeeds', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git push --force-with-lease origin main' => Process::result("To github.com:user/repo.git\n + abc123...def456 main -> main (forced update)"),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncForcePushWithLease')
        ->assertSet('lastOperation', 'force-push')
        ->assertSet('error', '')
        ->assertDispatched('status-updated');

    Process::assertRan('git push --force-with-lease origin main');
});

test('operations set isOperationRunning flag', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git push origin main' => Process::result('Success'),
    ]);

    $component = Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPush');

    $component->assertSet('isOperationRunning', false);
});

test('operations store output in operationOutput', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusClean()),
        'git push origin main' => Process::result("To github.com:user/repo.git\n   abc123..def456  main -> main"),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPush')
        ->assertSet('operationOutput', "To github.com:user/repo.git\n   abc123..def456  main -> main");
});

test('detached HEAD prevents push and pull operations', function () {
    Process::fake([
        'git status --porcelain=v2' => Process::result(GitOutputFixtures::statusDetachedHead()),
    ]);

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPush')
        ->assertSet('error', 'Cannot push from detached HEAD state');

    Livewire::test(SyncPanel::class, ['repoPath' => $this->testRepoPath])
        ->call('syncPull')
        ->assertSet('error', 'Cannot pull from detached HEAD state');
});
