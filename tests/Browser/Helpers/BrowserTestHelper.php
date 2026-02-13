<?php

declare(strict_types=1);

namespace Tests\Browser\Helpers;

use Illuminate\Support\Facades\Process;
use Tests\Mocks\GitOutputFixtures;

class BrowserTestHelper
{
    /**
     * The path to the mock Git repository used in tests.
     */
    public const MOCK_REPO_PATH = '/tmp/gitty-test-repo';

    /**
     * The path to the screenshots directory.
     */
    public const SCREENSHOTS_PATH = __DIR__.'/../screenshots';

    /**
     * Set up the mock Git repository directory.
     */
    public static function setupMockRepo(): void
    {
        $gitDir = self::MOCK_REPO_PATH.'/.git';
        if (! is_dir($gitDir)) {
            mkdir($gitDir, 0755, true);
        }
    }

    /**
     * Set up common Process::fake patterns for Git commands.
     * Returns an array of patterns that can be passed to Process::fake().
     */
    public static function getCommonProcessFakes(): array
    {
        return [
            'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
            'git branch --list --all --format=*' => Process::result(GitOutputFixtures::branchList()),
            'git log --oneline -n 20' => Process::result(GitOutputFixtures::logOneline()),
        ];
    }

    /**
     * Ensure the screenshots directory exists.
     */
    public static function ensureScreenshotsDirectory(): void
    {
        if (! is_dir(self::SCREENSHOTS_PATH)) {
            mkdir(self::SCREENSHOTS_PATH, 0755, true);
        }
    }
}
