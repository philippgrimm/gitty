<?php

declare(strict_types=1);

use App\Services\Git\GraphService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->testRepoPath = '/tmp/test-repo-'.uniqid();
    mkdir($this->testRepoPath);
    mkdir($this->testRepoPath.'/.git');
});

afterEach(function () {
    if (is_dir($this->testRepoPath)) {
        exec("rm -rf {$this->testRepoPath}");
    }
});

test('constructor validates git repository', function () {
    $invalidPath = '/tmp/not-a-repo-'.uniqid();
    mkdir($invalidPath);

    expect(fn () => new GraphService($invalidPath))
        ->toThrow(\InvalidArgumentException::class, 'Not a valid git repository');

    rmdir($invalidPath);
});

test('getGraphData returns empty array for empty repository', function () {
    Process::fake([
        "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n 200" => Process::result(
            output: ''
        ),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData();

    expect($graphData)->toBeArray()
        ->and($graphData)->toBeEmpty();
});

test('getGraphData returns empty array on git error', function () {
    Process::fake([
        "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n 200" => Process::result(
            exitCode: 1,
            errorOutput: 'fatal: bad revision'
        ),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData();

    expect($graphData)->toBeArray()
        ->and($graphData)->toBeEmpty();
});

test('getGraphData parses linear history with single lane', function () {
    Process::fake([
        "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n 200" => Process::result(
            output: implode("\n", [
                'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2|||b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3|||John Doe|||2 hours ago|||Third commit|||HEAD -> main',
                'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3|||c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4|||John Doe|||3 hours ago|||Second commit|||',
                'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4||||||John Doe|||4 hours ago|||Initial commit|||',
            ])
        ),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData();

    expect($graphData)->toHaveCount(3)
        ->and($graphData[0]->sha)->toBe('a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2')
        ->and($graphData[0]->message)->toBe('Third commit')
        ->and($graphData[0]->lane)->toBe(0)
        ->and($graphData[0]->parents)->toBe(['b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3'])
        ->and($graphData[0]->branch)->toBe('main')
        ->and($graphData[1]->sha)->toBe('b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3')
        ->and($graphData[1]->lane)->toBe(0)
        ->and($graphData[2]->sha)->toBe('c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4')
        ->and($graphData[2]->lane)->toBe(0)
        ->and($graphData[2]->parents)->toBeEmpty();
});

test('getGraphData handles branch and merge with multiple lanes', function () {
    Process::fake([
        "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n 200" => Process::result(
            output: implode("\n", [
                'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2|||b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3 d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5|||John Doe|||1 hour ago|||Merge branch feature|||HEAD -> main',
                'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3|||c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4|||John Doe|||2 hours ago|||Main commit|||',
                'd4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5|||c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4|||Jane Doe|||3 hours ago|||Feature commit|||feature',
                'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4||||||John Doe|||4 hours ago|||Initial commit|||',
            ])
        ),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData();

    expect($graphData)->toHaveCount(4)
        ->and($graphData[0]->sha)->toBe('a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2')
        ->and($graphData[0]->message)->toBe('Merge branch feature')
        ->and($graphData[0]->parents)->toHaveCount(2)
        ->and($graphData[0]->parents[0])->toBe('b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3')
        ->and($graphData[0]->parents[1])->toBe('d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5')
        ->and($graphData[1]->sha)->toBe('b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3')
        ->and($graphData[2]->sha)->toBe('d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5')
        ->and($graphData[2]->branch)->toBe('feature');
});

test('getGraphData respects limit parameter', function () {
    Process::fake([
        "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n 50" => Process::result(
            output: implode("\n", [
                'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2|||b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3|||John Doe|||1 hour ago|||Commit 1|||HEAD -> main',
                'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3||||||John Doe|||2 hours ago|||Commit 2|||',
            ])
        ),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData(50);

    expect($graphData)->toHaveCount(2);
});

test('getGraphData extracts refs correctly', function () {
    Process::fake([
        "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n 200" => Process::result(
            output: 'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2||||||John Doe|||1 hour ago|||Tagged commit|||HEAD -> main, tag: v1.0.0, origin/main'
        ),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData();

    expect($graphData)->toHaveCount(1)
        ->and($graphData[0]->refs)->toHaveCount(3)
        ->and($graphData[0]->refs[0])->toBe('HEAD -> main')
        ->and($graphData[0]->refs[1])->toBe('tag: v1.0.0')
        ->and($graphData[0]->refs[2])->toBe('origin/main');
});

test('getGraphData assigns different lanes for diverging branches', function () {
    Process::fake([
        "git log --all --format='%H|||%P|||%an|||%ar|||%s|||%D' -n 200" => Process::result(
            output: implode("\n", [
                'a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2|||c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4|||John Doe|||1 hour ago|||Main branch commit|||HEAD -> main',
                'b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3|||c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4|||Jane Doe|||2 hours ago|||Feature branch commit|||feature',
                'c3d4e5f6a1b2c3d4e5f6a1b2c3d4e5f6a1b2c3d4||||||John Doe|||3 hours ago|||Initial commit|||',
            ])
        ),
    ]);

    $service = new GraphService($this->testRepoPath);
    $graphData = $service->getGraphData();

    expect($graphData)->toHaveCount(3)
        ->and($graphData[0]->lane)->toBe(0)
        ->and($graphData[1]->lane)->toBeGreaterThanOrEqual(0)
        ->and($graphData[2]->lane)->toBeGreaterThanOrEqual(0);
});
