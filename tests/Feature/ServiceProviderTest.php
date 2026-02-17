<?php

use App\Services\Git\GitCacheService;
use App\Services\NotificationService;
use App\Services\RepoManager;
use App\Services\SettingsService;

test('GitCacheService is registered as singleton', function () {
    $instance1 = app(GitCacheService::class);
    $instance2 = app(GitCacheService::class);

    expect($instance1)->toBeInstanceOf(GitCacheService::class)
        ->and($instance1)->toBe($instance2);
});

test('SettingsService is registered as singleton', function () {
    $instance1 = app(SettingsService::class);
    $instance2 = app(SettingsService::class);

    expect($instance1)->toBeInstanceOf(SettingsService::class)
        ->and($instance1)->toBe($instance2);
});

test('RepoManager is registered as singleton', function () {
    $instance1 = app(RepoManager::class);
    $instance2 = app(RepoManager::class);

    expect($instance1)->toBeInstanceOf(RepoManager::class)
        ->and($instance1)->toBe($instance2);
});

test('NotificationService is registered as singleton', function () {
    $instance1 = app(NotificationService::class);
    $instance2 = app(NotificationService::class);

    expect($instance1)->toBeInstanceOf(NotificationService::class)
        ->and($instance1)->toBe($instance2);
});
