<?php

declare(strict_types=1);

use App\DTOs\BlameLine;
use App\Services\Git\BlameService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->testRepoPath = sys_get_temp_dir().'/test-repo-'.uniqid();
    mkdir($this->testRepoPath.'/.git', 0755, true);
});

afterEach(function () {
    if (is_dir($this->testRepoPath)) {
        exec("rm -rf {$this->testRepoPath}");
    }
});

it('parses porcelain blame output into BlameLine collection', function () {
    $porcelainOutput = implode("\n", [
        'abc1234567890abcdef1234567890abcdef12345 1 1 3',
        'author John Doe',
        'author-mail <john@example.com>',
        'author-time '.strtotime('-2 days'),
        'author-tz +0000',
        'committer John Doe',
        'committer-mail <john@example.com>',
        'committer-time '.strtotime('-2 days'),
        'committer-tz +0000',
        'summary Initial commit',
        'filename test.php',
        "\t<?php",
        'abc1234567890abcdef1234567890abcdef12345 2 2',
        "\t",
        'abc1234567890abcdef1234567890abcdef12345 3 3',
        "\techo 'hello';",
    ]);

    Process::fake([
        'git blame --porcelain *' => Process::result(output: $porcelainOutput),
    ]);

    Process::fake([
        'git blame --porcelain *' => Process::result(output: $porcelainOutput),
    ]);

    $service = new BlameService($this->testRepoPath);
    $result = $service->blame('test.php');

    expect($result)->toHaveCount(3)
        ->and($result->first())->toBeInstanceOf(BlameLine::class)
        ->and($result->first()->commitSha)->toBe('abc1234567890abcdef1234567890abcdef12345')
        ->and($result->first()->author)->toBe('John Doe')
        ->and($result->first()->lineNumber)->toBe(1)
        ->and($result->first()->content)->toBe('<?php')
        ->and($result->last()->content)->toBe("echo 'hello';");
});

it('handles multiple commits in blame output', function () {
    $time1 = strtotime('-5 days');
    $time2 = strtotime('-1 day');

    $porcelainOutput = implode("\n", [
        'aaaa234567890abcdef1234567890abcdef12345 1 1 1',
        'author Alice',
        'author-time '.$time1,
        'author-tz +0000',
        'summary First commit',
        'filename file.php',
        "\tline one",
        'bbbb234567890abcdef1234567890abcdef12345 2 2 1',
        'author Bob',
        'author-time '.$time2,
        'author-tz +0000',
        'summary Second commit',
        'filename file.php',
        "\tline two",
    ]);

    Process::fake([
        "git blame --porcelain 'file.php'" => Process::result(output: $porcelainOutput),
    ]);

    $service = new BlameService($this->testRepoPath);
    $result = $service->blame('file.php');

    expect($result)->toHaveCount(2)
        ->and($result[0]->author)->toBe('Alice')
        ->and($result[0]->commitSha)->toStartWith('aaaa')
        ->and($result[1]->author)->toBe('Bob')
        ->and($result[1]->commitSha)->toStartWith('bbbb');
});

it('throws exception when blame command fails', function () {
    Process::fake([
        "git blame --porcelain 'nonexistent.php'" => Process::result(exitCode: 128, errorOutput: 'fatal: no such path'),
    ]);

    $service = new BlameService($this->testRepoPath);
    $service->blame('nonexistent.php');
})->throws(\RuntimeException::class, 'Git blame failed');

it('handles empty file blame output', function () {
    Process::fake([
        "git blame --porcelain 'empty.php'" => Process::result(output: ''),
    ]);

    $service = new BlameService($this->testRepoPath);
    $result = $service->blame('empty.php');

    expect($result)->toHaveCount(0);
});

it('validates repository path on construction', function () {
    new BlameService('/nonexistent/path');
})->throws(\InvalidArgumentException::class, 'Not a valid git repository');

it('formats relative dates correctly', function () {
    $recentTime = strtotime('-30 minutes');

    $porcelainOutput = implode("\n", [
        'abc1234567890abcdef1234567890abcdef12345 1 1 1',
        'author Test',
        'author-time '.$recentTime,
        'author-tz +0000',
        'summary test',
        'filename test.php',
        "\tcode",
    ]);

    Process::fake([
        "git blame --porcelain 'test.php'" => Process::result(output: $porcelainOutput),
    ]);

    $service = new BlameService($this->testRepoPath);
    $result = $service->blame('test.php');

    expect($result->first()->date)->toContain('min');
});
