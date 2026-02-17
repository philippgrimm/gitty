<?php

declare(strict_types=1);

use App\Livewire\ShortcutHelp;
use Livewire\Livewire;

test('shortcut help modal is closed by default', function () {
    Livewire::test(ShortcutHelp::class)
        ->assertSet('showModal', false);
});

test('shortcut help modal opens on open-shortcut-help event', function () {
    Livewire::test(ShortcutHelp::class)
        ->dispatch('open-shortcut-help')
        ->assertSet('showModal', true);
});

test('shortcut help modal closes when closeModal is called', function () {
    Livewire::test(ShortcutHelp::class)
        ->dispatch('open-shortcut-help')
        ->assertSet('showModal', true)
        ->call('closeModal')
        ->assertSet('showModal', false);
});

test('shortcut help component renders', function () {
    Livewire::test(ShortcutHelp::class)
        ->assertSee('Keyboard Shortcuts')
        ->assertSee('General')
        ->assertSee('Staging')
        ->assertSee('Committing')
        ->assertSee('Navigation');
});
