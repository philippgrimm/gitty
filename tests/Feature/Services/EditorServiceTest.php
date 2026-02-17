<?php

declare(strict_types=1);

use App\Services\EditorService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->settingsService = Mockery::mock(SettingsService::class);
    $this->editorService = new EditorService($this->settingsService);
});

test('detectEditors returns only installed editors', function () {
    Process::fake([
        'which code' => Process::result(output: '/usr/local/bin/code'),
        'which cursor' => Process::result(output: '/usr/local/bin/cursor'),
        'which subl' => Process::result(exitCode: 1, output: ''),
        'which phpstorm' => Process::result(exitCode: 1, output: ''),
        'which zed' => Process::result(output: '/usr/local/bin/zed'),
    ]);

    $installed = $this->editorService->detectEditors();

    expect($installed)->toBe([
        'code' => 'VS Code',
        'cursor' => 'Cursor',
        'zed' => 'Zed',
    ]);
});

test('getDefaultEditor returns saved preference if valid', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('cursor');

    $editor = $this->editorService->getDefaultEditor();

    expect($editor)->toBe('cursor');
});

test('getDefaultEditor returns first detected editor if no preference', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('');

    Process::fake([
        'which code' => Process::result(exitCode: 1, output: ''),
        'which cursor' => Process::result(output: '/usr/local/bin/cursor'),
        'which subl' => Process::result(exitCode: 1, output: ''),
        'which phpstorm' => Process::result(exitCode: 1, output: ''),
        'which zed' => Process::result(exitCode: 1, output: ''),
    ]);

    $editor = $this->editorService->getDefaultEditor();

    expect($editor)->toBe('cursor');
});

test('getDefaultEditor returns null if no editors detected', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('');

    Process::fake([
        'which code' => Process::result(exitCode: 1, output: ''),
        'which cursor' => Process::result(exitCode: 1, output: ''),
        'which subl' => Process::result(exitCode: 1, output: ''),
        'which phpstorm' => Process::result(exitCode: 1, output: ''),
        'which zed' => Process::result(exitCode: 1, output: ''),
    ]);

    $editor = $this->editorService->getDefaultEditor();

    expect($editor)->toBeNull();
});

test('openFile constructs correct command for VS Code', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('code');

    Process::fake();

    $this->editorService->openFile('/path/to/repo', 'src/File.php', 42, 'code');

    Process::assertRan('code --goto /path/to/repo/src/File.php:42');
});

test('openFile constructs correct command for Cursor', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('cursor');

    Process::fake();

    $this->editorService->openFile('/path/to/repo', 'src/File.php', 10, 'cursor');

    Process::assertRan('cursor --goto /path/to/repo/src/File.php:10');
});

test('openFile constructs correct command for Sublime Text', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('subl');

    Process::fake();

    $this->editorService->openFile('/path/to/repo', 'src/File.php', 5, 'subl');

    Process::assertRan('subl /path/to/repo/src/File.php:5');
});

test('openFile constructs correct command for PhpStorm', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('phpstorm');

    Process::fake();

    $this->editorService->openFile('/path/to/repo', 'src/File.php', 20, 'phpstorm');

    Process::assertRan('phpstorm --line 20 /path/to/repo/src/File.php');
});

test('openFile constructs correct command for Zed', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('zed');

    Process::fake();

    $this->editorService->openFile('/path/to/repo', 'src/File.php', 15, 'zed');

    Process::assertRan('zed /path/to/repo/src/File.php:15');
});

test('openFile defaults to line 1 if not specified', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('code');

    Process::fake();

    $this->editorService->openFile('/path/to/repo', 'src/File.php', editorKey: 'code');

    Process::assertRan('code --goto /path/to/repo/src/File.php:1');
});

test('openFile throws exception if no editor configured', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('');

    Process::fake([
        'which code' => Process::result(exitCode: 1, output: ''),
        'which cursor' => Process::result(exitCode: 1, output: ''),
        'which subl' => Process::result(exitCode: 1, output: ''),
        'which phpstorm' => Process::result(exitCode: 1, output: ''),
        'which zed' => Process::result(exitCode: 1, output: ''),
    ]);

    expect(fn () => $this->editorService->openFile('/path/to/repo', 'src/File.php'))
        ->toThrow(\RuntimeException::class, 'No editor configured or detected');
});

test('openFile throws exception if invalid editor key provided', function () {
    $this->settingsService->shouldReceive('get')
        ->with('external_editor', '')
        ->andReturn('');

    Process::fake();

    expect(fn () => $this->editorService->openFile('/path/to/repo', 'src/File.php', 1, 'invalid'))
        ->toThrow(\RuntimeException::class, 'No editor configured or detected');
});
