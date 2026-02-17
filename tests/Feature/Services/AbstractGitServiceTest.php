<?php

declare(strict_types=1);

use App\Services\Git\AbstractGitService;
use App\Services\Git\GitCacheService;
use App\Services\Git\GitCommandRunner;
use Tests\Helpers\GitTestHelper;

class ConcreteGitServiceForTest extends AbstractGitService
{
    public function getCache(): GitCacheService
    {
        return $this->cache;
    }

    public function getCommandRunner(): GitCommandRunner
    {
        return $this->commandRunner;
    }

    public function getRepoPath(): string
    {
        return $this->repoPath;
    }
}

beforeEach(function () {
    $this->testRepoPath = sys_get_temp_dir().'/gitty-test-abstract-'.uniqid();
    GitTestHelper::createTestRepo($this->testRepoPath);
});

afterEach(function () {
    GitTestHelper::cleanupTestRepo($this->testRepoPath);
});

test('AbstractGitService accepts valid git repository path', function () {
    $service = new ConcreteGitServiceForTest($this->testRepoPath);
    expect($service->getRepoPath())->toBe($this->testRepoPath);
});

test('AbstractGitService throws InvalidArgumentException for non-git directory', function () {
    $nonGitPath = sys_get_temp_dir().'/gitty-test-nongit-'.uniqid();
    mkdir($nonGitPath, 0755, true);

    try {
        new ConcreteGitServiceForTest($nonGitPath);
    } finally {
        rmdir($nonGitPath);
    }
})->throws(InvalidArgumentException::class, 'Not a valid git repository');

test('AbstractGitService throws InvalidArgumentException for non-existent directory', function () {
    new ConcreteGitServiceForTest('/tmp/does-not-exist-'.uniqid());
})->throws(InvalidArgumentException::class, 'Not a valid git repository');

test('AbstractGitService provides GitCacheService instance', function () {
    $service = new ConcreteGitServiceForTest($this->testRepoPath);
    expect($service->getCache())->toBeInstanceOf(GitCacheService::class);
});

test('AbstractGitService provides GitCommandRunner instance', function () {
    $service = new ConcreteGitServiceForTest($this->testRepoPath);
    expect($service->getCommandRunner())->toBeInstanceOf(GitCommandRunner::class);
});

test('AbstractGitService handles repo path with trailing slash', function () {
    $service = new ConcreteGitServiceForTest($this->testRepoPath.'/');
    expect($service->getRepoPath())->toBe($this->testRepoPath.'/');
    expect($service->getCache())->toBeInstanceOf(GitCacheService::class);
});
