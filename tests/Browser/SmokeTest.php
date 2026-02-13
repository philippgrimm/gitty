<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('homepage loads successfully', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $page = visit('/');

    $page->assertSee('Gitty');
    $page->screenshot(fullPage: true, filename: 'homepage-smoke-test');
});
