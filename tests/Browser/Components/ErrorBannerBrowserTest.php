<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\Browser\Helpers\BrowserTestHelper;
use Tests\Mocks\GitOutputFixtures;

uses(RefreshDatabase::class);

test('error banner is hidden by default', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $page = visit('/');

    $page->assertDontSee('Error');
    $page->assertDontSee('Test error message');
    $page->screenshot(fullPage: true, filename: 'error-banner-hidden');
});

test('error banner shows error message when dispatched', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $page = visit('/');

    $page->script("window.Livewire.dispatch('show-error', { message: 'Test error message', type: 'error', persistent: false })");

    $page->waitForText('Test error message');
    $page->assertSee('Error');
    $page->assertSee('Test error message');
    $page->screenshot(fullPage: true, filename: 'error-banner-error');
});

test('error banner shows warning message', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $page = visit('/');

    $page->script("window.Livewire.dispatch('show-error', { message: 'Warning message', type: 'warning', persistent: false })");

    $page->waitForText('Warning message');
    $page->assertSee('Warning');
    $page->assertSee('Warning message');
    $page->screenshot(fullPage: true, filename: 'error-banner-warning');
});

test('error banner can be dismissed manually', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    Process::fake([
        'git status --porcelain=v2 --branch' => Process::result(GitOutputFixtures::statusClean()),
    ]);

    $page = visit('/');

    $page->script("window.Livewire.dispatch('show-error', { message: 'Dismissible error', type: 'error', persistent: true })");

    $page->waitForText('Dismissible error');
    $page->assertSee('Dismissible error');

    $page->click('button[aria-label="Dismiss"]');
    $page->assertDontSee('Dismissible error');
    $page->screenshot(fullPage: true, filename: 'error-banner-dismissed');
});
