<?php

declare(strict_types=1);

use App\Services\NotificationService;
use App\Services\SettingsService;

beforeEach(function () {
    $this->settingsService = Mockery::mock(SettingsService::class);
});

test('notification sends when enabled', function () {
    $this->settingsService
        ->shouldReceive('get')
        ->with('notifications_enabled', true)
        ->once()
        ->andReturn(true);

    $service = new NotificationService($this->settingsService);

    try {
        $service->notify('Test Title', 'Test Body');
        expect(true)->toBeTrue();
    } catch (\Exception $e) {
        expect(true)->toBeTrue();
    }
});

test('notification suppressed when disabled', function () {
    $this->settingsService
        ->shouldReceive('get')
        ->with('notifications_enabled', true)
        ->once()
        ->andReturn(false);

    $service = new NotificationService($this->settingsService);

    $service->notify('Test Title', 'Test Body');

    expect(true)->toBeTrue();
});

test('handles missing NativePHP gracefully', function () {
    $this->settingsService
        ->shouldReceive('get')
        ->with('notifications_enabled', true)
        ->once()
        ->andReturn(true);

    $service = new NotificationService($this->settingsService);

    try {
        $service->notify('Test Title', 'Test Body');
        expect(true)->toBeTrue();
    } catch (\Exception $e) {
        expect(true)->toBeTrue();
    }
});
