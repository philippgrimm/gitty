<?php

declare(strict_types=1);

use App\Livewire\ErrorBanner;
use Livewire\Livewire;

test('component mounts with default state', function () {
    Livewire::test(ErrorBanner::class)
        ->assertSet('visible', false)
        ->assertSet('message', '')
        ->assertSet('type', 'error')
        ->assertSet('persistent', false);
});

test('component shows error when show-error event is dispatched', function () {
    Livewire::test(ErrorBanner::class)
        ->dispatch('show-error', message: 'Test error message', type: 'error', persistent: false)
        ->assertSet('visible', true)
        ->assertSet('message', 'Test error message')
        ->assertSet('type', 'error')
        ->assertSet('persistent', false)
        ->assertSee('Test error message');
});

test('component shows warning message', function () {
    Livewire::test(ErrorBanner::class)
        ->dispatch('show-error', message: 'Warning message', type: 'warning', persistent: false)
        ->assertSet('type', 'warning')
        ->assertSee('Warning message');
});

test('component shows info message', function () {
    Livewire::test(ErrorBanner::class)
        ->dispatch('show-error', message: 'Info message', type: 'info', persistent: false)
        ->assertSet('type', 'info')
        ->assertSee('Info message');
});

test('component dismisses error when dismiss method is called', function () {
    Livewire::test(ErrorBanner::class)
        ->dispatch('show-error', message: 'Test error', type: 'error', persistent: false)
        ->assertSet('visible', true)
        ->call('dismiss')
        ->assertSet('visible', false);
});

test('component sets persistent flag correctly', function () {
    Livewire::test(ErrorBanner::class)
        ->dispatch('show-error', message: 'Persistent error', type: 'error', persistent: true)
        ->assertSet('persistent', true);
});

test('component is hidden by default', function () {
    Livewire::test(ErrorBanner::class)
        ->assertDontSee('Test error message');
});

test('component handles empty message', function () {
    Livewire::test(ErrorBanner::class)
        ->dispatch('show-error', message: '', type: 'error', persistent: false)
        ->assertSet('visible', true)
        ->assertSet('message', '');
});
