<?php

declare(strict_types=1);

use App\DTOs\Remote;
use App\Services\Git\RemoteService;
use Illuminate\Support\Facades\Process;
use Tests\Mocks\GitOutputFixtures;

test('it validates repository path has .git directory', function () {
    expect(fn () => new RemoteService('/invalid/path'))
        ->toThrow(InvalidArgumentException::class, 'Not a valid git repository');
});

test('it lists all remotes', function () {
    Process::fake([
        'git remote -v' => GitOutputFixtures::remoteList(),
    ]);

    $service = new RemoteService('/tmp/gitty-test-repo');
    $remotes = $service->remotes();

    expect($remotes)->toHaveCount(2)
        ->and($remotes->first())->toBeInstanceOf(Remote::class)
        ->and($remotes->first()->name)->toBe('origin')
        ->and($remotes->first()->fetchUrl)->toBe('git@github.com:user/project.git');
});

test('it pushes to remote', function () {
    Process::fake();

    $service = new RemoteService('/tmp/gitty-test-repo');
    $service->push('origin', 'main');

    Process::assertRan("git push 'origin' 'main'");
});

test('it pulls from remote', function () {
    Process::fake();

    $service = new RemoteService('/tmp/gitty-test-repo');
    $service->pull('origin', 'main');

    Process::assertRan("git pull 'origin' 'main'");
});

test('it fetches from specific remote', function () {
    Process::fake();

    $service = new RemoteService('/tmp/gitty-test-repo');
    $service->fetch('origin');

    Process::assertRan("git fetch 'origin'");
});

test('it fetches from all remotes', function () {
    Process::fake();

    $service = new RemoteService('/tmp/gitty-test-repo');
    $service->fetchAll();

    Process::assertRan('git fetch --all');
});
