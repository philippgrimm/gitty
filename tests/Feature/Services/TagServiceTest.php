<?php

declare(strict_types=1);

use App\Services\Git\TagService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath.'/.git')) {
        mkdir($this->testRepoPath.'/.git', 0755, true);
    }
});

test('tags returns collection of tags with metadata', function () {
    Process::fake([
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(
            "v2.0.0|||abc1234|||2 days ago|||Major release\nv1.5.0|||def5678|||1 week ago|||Feature update\nv1.0.0|||ghi9012|||2 weeks ago|||Initial release"
        ),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tags = $tagService->tags();

    expect($tags)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($tags)->toHaveCount(3)
        ->and($tags->first())->toHaveKeys(['name', 'sha', 'date', 'message'])
        ->and($tags->first()['name'])->toBe('v2.0.0')
        ->and($tags->first()['sha'])->toBe('abc1234')
        ->and($tags->first()['date'])->toBe('2 days ago')
        ->and($tags->first()['message'])->toBe('Major release');
});

test('tags returns empty collection when no tags exist', function () {
    Process::fake([
        "git tag -l --sort=-creatordate --format='%(refname:short)|||%(objectname:short)|||%(creatordate:relative)|||%(contents:subject)'" => Process::result(''),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tags = $tagService->tags();

    expect($tags)->toBeInstanceOf(\Illuminate\Support\Collection::class)
        ->and($tags)->toHaveCount(0);
});

test('createTag creates lightweight tag', function () {
    Process::fake([
        "git tag 'v1.0.0'" => Process::result(''),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tagService->createTag('v1.0.0');

    Process::assertRan("git tag 'v1.0.0'");
});

test('createTag creates annotated tag with message', function () {
    Process::fake([
        "git tag -a 'v2.0.0' '-m' 'Release version 2.0'" => Process::result(''),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tagService->createTag('v2.0.0', 'Release version 2.0');

    Process::assertRan("git tag -a 'v2.0.0' '-m' 'Release version 2.0'");
});

test('createTag creates tag at specific commit', function () {
    Process::fake([
        "git tag 'v1.5.0' 'abc1234'" => Process::result(''),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tagService->createTag('v1.5.0', null, 'abc1234');

    Process::assertRan("git tag 'v1.5.0' 'abc1234'");
});

test('createTag creates annotated tag at specific commit', function () {
    Process::fake([
        "git tag -a 'v1.5.0' '-m' 'Hotfix release' 'abc1234'" => Process::result(''),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tagService->createTag('v1.5.0', 'Hotfix release', 'abc1234');

    Process::assertRan("git tag -a 'v1.5.0' '-m' 'Hotfix release' 'abc1234'");
});

test('createTag throws exception on failure', function () {
    Process::fake([
        "git tag 'v1.0.0'" => Process::result('', 'fatal: tag already exists', 1),
    ]);

    $tagService = new TagService($this->testRepoPath);

    expect(fn () => $tagService->createTag('v1.0.0'))
        ->toThrow(\RuntimeException::class, 'Failed to create tag');
});

test('deleteTag deletes a tag', function () {
    Process::fake([
        "git tag -d 'v1.0.0'" => Process::result('Deleted tag v1.0.0'),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tagService->deleteTag('v1.0.0');

    Process::assertRan("git tag -d 'v1.0.0'");
});

test('deleteTag throws exception on failure', function () {
    Process::fake([
        "git tag -d 'v1.0.0'" => Process::result('', 'fatal: tag not found', 1),
    ]);

    $tagService = new TagService($this->testRepoPath);

    expect(fn () => $tagService->deleteTag('v1.0.0'))
        ->toThrow(\RuntimeException::class, 'Failed to delete tag');
});

test('pushTag pushes tag to default remote', function () {
    Process::fake([
        "git push origin 'v1.0.0'" => Process::result(''),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tagService->pushTag('v1.0.0');

    Process::assertRan("git push origin 'v1.0.0'");
});

test('pushTag pushes tag to specified remote', function () {
    Process::fake([
        "git push upstream 'v2.0.0'" => Process::result(''),
    ]);

    $tagService = new TagService($this->testRepoPath);
    $tagService->pushTag('v2.0.0', 'upstream');

    Process::assertRan("git push upstream 'v2.0.0'");
});

test('pushTag throws exception on failure', function () {
    Process::fake([
        "git push origin 'v1.0.0'" => Process::result('', 'fatal: remote not found', 1),
    ]);

    $tagService = new TagService($this->testRepoPath);

    expect(fn () => $tagService->pushTag('v1.0.0'))
        ->toThrow(\RuntimeException::class, 'Failed to push tag');
});

test('constructor throws exception for invalid repository', function () {
    expect(fn () => new TagService('/invalid/path'))
        ->toThrow(\InvalidArgumentException::class, 'Not a valid git repository');
});
