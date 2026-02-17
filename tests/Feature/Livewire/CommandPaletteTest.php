<?php

declare(strict_types=1);

use App\Livewire\CommandPalette;
use Livewire\Livewire;

test('command palette is closed by default', function () {
    Livewire::test(CommandPalette::class)
        ->assertSet('isOpen', false)
        ->assertSet('mode', 'search');
});

test('command palette opens on open-command-palette event', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->assertSet('isOpen', true)
        ->assertSet('mode', 'search');
});

test('command palette closes when close is called', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->assertSet('isOpen', true)
        ->call('close')
        ->assertSet('isOpen', false);
});

test('command palette toggles on toggle-command-palette event', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('toggle-command-palette')
        ->assertSet('isOpen', true)
        ->dispatch('toggle-command-palette')
        ->assertSet('isOpen', false)
        ->dispatch('toggle-command-palette')
        ->assertSet('isOpen', true);
});

test('command registry returns all expected commands', function () {
    $commands = CommandPalette::getCommands();

    expect($commands)->toHaveCount(24);

    $ids = collect($commands)->pluck('id');

    expect($ids)->toContain('stage-all')
        ->toContain('commit')
        ->toContain('push')
        ->toContain('create-branch')
        ->toContain('create-tag')
        ->toContain('toggle-sidebar')
        ->toContain('open-settings')
        ->toContain('show-shortcuts');
});

test('search filters commands by label', function () {
    $component = Livewire::test(CommandPalette::class)
        ->set('query', 'push');

    $labels = collect($component->get('filteredCommands'))->pluck('label');

    expect($labels)->toContain('Push')
        ->toContain('Commit and Push')
        ->toContain('Force Push (with Lease)');
});

test('search filters commands by keywords', function () {
    $component = Livewire::test(CommandPalette::class)
        ->set('query', 'add');

    $labels = collect($component->get('filteredCommands'))->pluck('label');

    expect($labels)->toContain('Stage All');
});

test('empty search query returns all commands', function () {
    $component = Livewire::test(CommandPalette::class)
        ->set('repoPath', '/tmp/test-repo')
        ->set('query', '');

    expect($component->get('filteredCommands'))->toHaveCount(24);
});

test('search with no matches returns empty', function () {
    $component = Livewire::test(CommandPalette::class)
        ->set('query', 'xyznonexistent');

    expect($component->get('filteredCommands'))->toHaveCount(0);
});

test('execute command dispatches correct event', function () {
    Livewire::test(CommandPalette::class)
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'stage-all')
        ->assertDispatched('keyboard-stage-all');
});

test('execute command closes palette', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'stage-all')
        ->assertSet('isOpen', false);
});

test('execute command with requiresInput transitions to input mode', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'create-branch')
        ->assertSet('mode', 'input')
        ->assertSet('inputCommand', 'create-branch');
});

test('cancel input returns to search mode', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'create-branch')
        ->assertSet('mode', 'input')
        ->call('cancelInput')
        ->assertSet('mode', 'search')
        ->assertSet('inputCommand', null);
});

test('submit input with empty value shows error', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'create-branch')
        ->set('inputValue', '')
        ->call('submitInput')
        ->assertSet('inputError', 'Branch name is required')
        ->assertSet('isOpen', true);
});

test('submit input dispatches palette-create-branch event', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'create-branch')
        ->set('inputValue', 'feature-test')
        ->call('submitInput')
        ->assertDispatched('palette-create-branch', name: 'feature-test');
});

test('successful input submission closes palette', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'create-branch')
        ->set('inputValue', 'feature-test')
        ->call('submitInput')
        ->assertSet('isOpen', false);
});

test('disabled commands cannot be executed', function () {
    Livewire::test(CommandPalette::class)
        ->set('repoPath', '')
        ->call('executeCommand', 'stage-all')
        ->assertNotDispatched('keyboard-stage-all');
});

test('create-branch input mode prefills feature/ in input value', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette')
        ->set('repoPath', '/tmp/test-repo')
        ->call('executeCommand', 'create-branch')
        ->assertSet('inputValue', 'feature/');
});

test('open-command-palette-create-branch event opens palette in create-branch input mode', function () {
    Livewire::test(CommandPalette::class)
        ->dispatch('open-command-palette-create-branch')
        ->assertSet('isOpen', true)
        ->assertSet('mode', 'input')
        ->assertSet('inputCommand', 'create-branch')
        ->assertSet('inputValue', 'feature/');
});
