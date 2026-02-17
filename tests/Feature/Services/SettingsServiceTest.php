<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new SettingsService;
});

test('get returns default value when setting does not exist', function () {
    $value = $this->service->get('auto_fetch_interval');

    expect($value)->toBe(180);
});

test('get returns custom default when setting does not exist', function () {
    $value = $this->service->get('nonexistent_key', 'custom_default');

    expect($value)->toBe('custom_default');
});

test('get returns stored value when setting exists', function () {
    Setting::create(['key' => 'auto_fetch_interval', 'value' => '300']);

    $value = $this->service->get('auto_fetch_interval');

    expect($value)->toBe(300);
});

test('get casts boolean strings to bool for known boolean settings', function () {
    Setting::create(['key' => 'confirm_discard', 'value' => '1']);
    Setting::create(['key' => 'confirm_force_push', 'value' => '0']);

    expect($this->service->get('confirm_discard'))->toBeTrue();
    expect($this->service->get('confirm_force_push'))->toBeFalse();
});

test('set creates a new setting', function () {
    $this->service->set('auto_fetch_interval', 240);

    $setting = Setting::where('key', 'auto_fetch_interval')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->toBe('240');
});

test('set updates an existing setting', function () {
    Setting::create(['key' => 'theme', 'value' => 'light']);

    $this->service->set('theme', 'dark');

    $setting = Setting::where('key', 'theme')->first();
    expect($setting->value)->toBe('dark');
    expect(Setting::where('key', 'theme')->count())->toBe(1);
});

test('set stores boolean values as strings', function () {
    $this->service->set('confirm_discard', true);
    $this->service->set('show_untracked', false);

    $confirm = Setting::where('key', 'confirm_discard')->first();
    $show = Setting::where('key', 'show_untracked')->first();

    expect($confirm->value)->toBe('1');
    expect($show->value)->toBe('0');
});

test('all returns all settings merged with defaults', function () {
    Setting::create(['key' => 'theme', 'value' => 'light']);
    Setting::create(['key' => 'auto_fetch_interval', 'value' => '300']);

    $all = $this->service->all();

    expect($all)->toBeArray();
    expect($all)->toHaveCount(9);
    expect($all['theme'])->toBe('light');
    expect($all['auto_fetch_interval'])->toBe(300);
    expect($all['default_branch'])->toBe('main');
    expect($all['confirm_discard'])->toBeTrue();
});

test('reset deletes all settings from database', function () {
    Setting::create(['key' => 'theme', 'value' => 'light']);
    Setting::create(['key' => 'auto_fetch_interval', 'value' => '300']);

    expect(Setting::count())->toBe(2);

    $this->service->reset();

    expect(Setting::count())->toBe(0);
});

test('defaults returns all default values', function () {
    $defaults = $this->service->defaults();

    expect($defaults)->toBeArray();
    expect($defaults)->toHaveCount(9);
    expect($defaults['auto_fetch_interval'])->toBe(180);
    expect($defaults['external_editor'])->toBe('');
    expect($defaults['theme'])->toBe('dark');
    expect($defaults['default_branch'])->toBe('main');
    expect($defaults['confirm_discard'])->toBeTrue();
    expect($defaults['confirm_force_push'])->toBeTrue();
    expect($defaults['show_untracked'])->toBeTrue();
    expect($defaults['diff_context_lines'])->toBe(3);
});

test('get returns integer for numeric settings', function () {
    Setting::create(['key' => 'diff_context_lines', 'value' => '5']);

    $value = $this->service->get('diff_context_lines');

    expect($value)->toBe(5);
    expect($value)->toBeInt();
});
