<?php

declare(strict_types=1);

use App\Livewire\SettingsModal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Browser\Helpers\BrowserTestHelper;

uses(RefreshDatabase::class);

test('settings modal component is hidden by default', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $component = Livewire::test(SettingsModal::class);

    $component->assertSet('showModal', false);
});

test('settings modal component opens when event is dispatched', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $component = Livewire::test(SettingsModal::class);

    $component->dispatch('open-settings');

    $component->assertSet('showModal', true);
    $component->assertSee('Settings');
    $component->assertSee('Configure application settings');
    $component->assertSee('Auto-fetch interval');
    $component->assertSee('Default branch');
});

test('settings modal component can be closed', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $component = Livewire::test(SettingsModal::class);

    $component->dispatch('open-settings');
    $component->assertSet('showModal', true);

    $component->call('closeModal');
    $component->assertSet('showModal', false);
});

test('settings modal component can save changes', function () {
    BrowserTestHelper::setupMockRepo();
    BrowserTestHelper::ensureScreenshotsDirectory();

    $component = Livewire::test(SettingsModal::class);

    $component->dispatch('open-settings');
    $component->assertSet('showModal', true);

    $component->set('autoFetchInterval', 300);
    $component->call('save');

    $component->assertSet('showModal', false);
    $component->assertDispatched('settings-updated');
});
