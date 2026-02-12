<?php

declare(strict_types=1);

use App\Livewire\SettingsModal;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('component mounts with default settings', function () {
    Livewire::test(SettingsModal::class)
        ->assertSet('autoFetchInterval', 180)
        ->assertSet('externalEditor', '')
        ->assertSet('theme', 'dark')
        ->assertSet('defaultBranch', 'main')
        ->assertSet('confirmDiscard', true)
        ->assertSet('confirmForcePush', true)
        ->assertSet('showUntracked', true)
        ->assertSet('diffContextLines', 3)
        ->assertSet('showModal', false);
});

test('component loads custom settings from database', function () {
    Setting::create(['key' => 'auto_fetch_interval', 'value' => '300']);
    Setting::create(['key' => 'theme', 'value' => 'light']);
    Setting::create(['key' => 'confirm_discard', 'value' => '0']);

    Livewire::test(SettingsModal::class)
        ->assertSet('autoFetchInterval', 300)
        ->assertSet('theme', 'light')
        ->assertSet('confirmDiscard', false);
});

test('component saves all settings to database', function () {
    Livewire::test(SettingsModal::class)
        ->set('autoFetchInterval', 240)
        ->set('theme', 'light')
        ->set('confirmDiscard', false)
        ->call('save')
        ->assertDispatched('settings-updated')
        ->assertSet('showModal', false);

    expect(Setting::where('key', 'auto_fetch_interval')->value('value'))->toBe('240');
    expect(Setting::where('key', 'theme')->value('value'))->toBe('light');
    expect(Setting::where('key', 'confirm_discard')->value('value'))->toBe('0');
});

test('component resets to defaults', function () {
    Setting::create(['key' => 'theme', 'value' => 'light']);
    Setting::create(['key' => 'auto_fetch_interval', 'value' => '300']);

    Livewire::test(SettingsModal::class)
        ->call('resetToDefaults')
        ->assertSet('theme', 'dark')
        ->assertSet('autoFetchInterval', 180);

    expect(Setting::count())->toBe(0);
});

test('component opens modal', function () {
    Livewire::test(SettingsModal::class)
        ->assertSet('showModal', false)
        ->call('openModal')
        ->assertSet('showModal', true);
});

test('component closes modal', function () {
    Livewire::test(SettingsModal::class)
        ->set('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false);
});

test('component listens for open-settings event', function () {
    Livewire::test(SettingsModal::class)
        ->assertSet('showModal', false)
        ->dispatch('open-settings')
        ->assertSet('showModal', true);
});

test('component saves all 8 settings', function () {
    Livewire::test(SettingsModal::class)
        ->set('autoFetchInterval', 120)
        ->set('externalEditor', 'code')
        ->set('theme', 'system')
        ->set('defaultBranch', 'develop')
        ->set('confirmDiscard', false)
        ->set('confirmForcePush', false)
        ->set('showUntracked', false)
        ->set('diffContextLines', 5)
        ->call('save');

    expect(Setting::count())->toBe(8);
    expect(Setting::where('key', 'external_editor')->value('value'))->toBe('code');
    expect(Setting::where('key', 'default_branch')->value('value'))->toBe('develop');
    expect(Setting::where('key', 'diff_context_lines')->value('value'))->toBe('5');
});
