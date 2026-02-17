<?php

declare(strict_types=1);

use App\Services\SettingsService;
use Livewire\Livewire;

uses()->beforeEach(function (): void {
    $this->artisan('migrate:fresh');
});

test('theme setting defaults to dark', function (): void {
    $service = new SettingsService;

    expect($service->get('theme'))->toBe('dark');
});

test('theme can be set to light', function (): void {
    $service = new SettingsService;
    $service->set('theme', 'light');

    expect($service->get('theme'))->toBe('light');
});

test('theme can be set to dark', function (): void {
    $service = new SettingsService;
    $service->set('theme', 'dark');

    expect($service->get('theme'))->toBe('dark');
});

test('theme persists across instances', function (): void {
    $service1 = new SettingsService;
    $service1->set('theme', 'dark');

    $service2 = new SettingsService;
    expect($service2->get('theme'))->toBe('dark');
});

test('settings modal allows theme selection', function (): void {
    Livewire::test(\App\Livewire\SettingsModal::class)
        ->assertSet('theme', 'dark')
        ->set('theme', 'dark')
        ->call('save')
        ->assertDispatched('theme-updated', theme: 'dark');
});

test('theme setting resets to dark on defaults', function (): void {
    $service = new SettingsService;
    $service->set('theme', 'light');
    $service->reset();

    expect($service->get('theme'))->toBe('dark');
});
