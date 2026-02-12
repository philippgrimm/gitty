<?php

declare(strict_types=1);

use App\Livewire\AppLayout;
use Livewire\Livewire;

beforeEach(function () {
    $this->testRepoPath = '/tmp/gitty-test-repo';
    if (! is_dir($this->testRepoPath . '/.git')) {
        mkdir($this->testRepoPath . '/.git', 0755, true);
    }
});

test('component mounts with repo path', function () {
    Livewire::test(AppLayout::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('repoPath', $this->testRepoPath)
        ->assertSet('sidebarCollapsed', false);
});

test('component shows empty state when no repo path provided', function () {
    Livewire::test(AppLayout::class)
        ->assertSet('repoPath', '')
        ->assertSee('No Repository Selected');
});

test('component validates git repository on mount', function () {
    $invalidPath = '/tmp/not-a-git-repo';
    if (! is_dir($invalidPath)) {
        mkdir($invalidPath, 0755, true);
    }

    Livewire::test(AppLayout::class, ['repoPath' => $invalidPath])
        ->assertSet('repoPath', '')
        ->assertSee('No Repository Selected');
});

test('component toggles sidebar collapsed state', function () {
    Livewire::test(AppLayout::class, ['repoPath' => $this->testRepoPath])
        ->assertSet('sidebarCollapsed', false)
        ->call('toggleSidebar')
        ->assertSet('sidebarCollapsed', true)
        ->call('toggleSidebar')
        ->assertSet('sidebarCollapsed', false);
});
