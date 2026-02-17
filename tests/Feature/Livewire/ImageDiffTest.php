<?php

declare(strict_types=1);

use App\Livewire\DiffViewer;
use Illuminate\Support\Facades\Process;
use Livewire\Livewire;

function callPrivateMethod(object $object, string $method, ...$args): mixed
{
    $reflection = new ReflectionClass($object);
    $method = $reflection->getMethod($method);
    $method->setAccessible(true);

    return $method->invoke($object, ...$args);
}

beforeEach(function () {
    $this->repoPath = '/fake/repo/path';
});

test('isImageFile returns true for image extensions', function () {
    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);
    $instance = $component->instance();

    $imageExtensions = ['png', 'jpg', 'jpeg', 'gif', 'svg', 'webp', 'ico', 'bmp'];

    foreach ($imageExtensions as $ext) {
        expect(callPrivateMethod($instance, 'isImageFile', "test-file.{$ext}"))->toBeTrue();
    }
});

test('isImageFile returns true for uppercase extensions', function () {
    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);
    $instance = $component->instance();

    expect(callPrivateMethod($instance, 'isImageFile', 'test-file.PNG'))->toBeTrue();
    expect(callPrivateMethod($instance, 'isImageFile', 'test-file.JPG'))->toBeTrue();
    expect(callPrivateMethod($instance, 'isImageFile', 'test-file.JPEG'))->toBeTrue();
});

test('isImageFile returns false for non-image extensions', function () {
    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);
    $instance = $component->instance();

    $nonImageExtensions = ['txt', 'php', 'js', 'css', 'html', 'md', 'json'];

    foreach ($nonImageExtensions as $ext) {
        expect(callPrivateMethod($instance, 'isImageFile', "test-file.{$ext}"))->toBeFalse();
    }
});

test('getImageData returns correct structure for new image', function () {
    Process::fake([
        'git show HEAD:"new-image.png" 2>/dev/null' => Process::result(output: '', exitCode: 1),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);

    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    $tempFile = sys_get_temp_dir().'/test-new-image.png';
    file_put_contents($tempFile, $imageContent);

    $component->set('repoPath', sys_get_temp_dir());

    $imageData = callPrivateMethod($component->instance(), 'getImageData', 'test-new-image.png');

    expect($imageData)->toBeArray()
        ->and($imageData)->toHaveKeys(['oldImage', 'newImage', 'oldSize', 'newSize', 'extension'])
        ->and($imageData['oldImage'])->toBeNull()
        ->and($imageData['newImage'])->toStartWith('data:image/png;base64,')
        ->and($imageData['oldSize'])->toBe(0)
        ->and($imageData['newSize'])->toBeGreaterThan(0)
        ->and($imageData['extension'])->toBe('png');

    unlink($tempFile);
});

test('getImageData returns correct structure for deleted image', function () {
    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    Process::fake([
        'git show HEAD:"deleted-image.png" 2>/dev/null' => Process::result(output: $imageContent, exitCode: 0),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);
    $instance = $component->instance();

    $imageData = callPrivateMethod($instance, 'getImageData', 'deleted-image.png');

    expect($imageData)->toBeArray()
        ->and($imageData['oldImage'])->toStartWith('data:image/png;base64,')
        ->and($imageData['newImage'])->toBeNull()
        ->and($imageData['oldSize'])->toBeGreaterThan(0)
        ->and($imageData['newSize'])->toBe(0)
        ->and($imageData['extension'])->toBe('png');
});

test('getImageData returns correct structure for modified image', function () {
    $oldImageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
    $newImageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAIAAAACCAYAAABytg0kAAAAFUlEQVR42mNk+M9Qz8DAwMDIwMAAAAFCAQFAQs8AAAAASUVORK5CYII=');

    Process::fake([
        'git show HEAD:"test-modified-image.png" 2>/dev/null' => Process::result(output: $oldImageContent, exitCode: 0),
    ]);

    $tempFile = sys_get_temp_dir().'/test-modified-image.png';
    file_put_contents($tempFile, $newImageContent);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => sys_get_temp_dir()]);
    $instance = $component->instance();

    $imageData = callPrivateMethod($instance, 'getImageData', 'test-modified-image.png');

    expect($imageData)->toBeArray()
        ->and($imageData['oldImage'])->not()->toBeNull()
        ->and($imageData['oldImage'])->toStartWith('data:image/png;base64,')
        ->and($imageData['newImage'])->not()->toBeNull()
        ->and($imageData['newImage'])->toStartWith('data:image/png;base64,')
        ->and($imageData['oldSize'])->toBeGreaterThan(0)
        ->and($imageData['newSize'])->toBeGreaterThan(0)
        ->and($imageData['extension'])->toBe('png');

    unlink($tempFile);
});

test('getMimeType returns correct mime types', function () {
    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);
    $instance = $component->instance();

    $mimeTypes = [
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'webp' => 'image/webp',
        'ico' => 'image/x-icon',
        'bmp' => 'image/bmp',
    ];

    foreach ($mimeTypes as $ext => $mime) {
        expect(callPrivateMethod($instance, 'getMimeType', $ext))->toBe($mime);
    }
});

test('formatFileSize formats bytes correctly', function () {
    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);
    $instance = $component->instance();

    expect(callPrivateMethod($instance, 'formatFileSize', 500))->toBe('500 B')
        ->and(callPrivateMethod($instance, 'formatFileSize', 1024))->toBe('1 KB')
        ->and(callPrivateMethod($instance, 'formatFileSize', 1536))->toBe('1.5 KB')
        ->and(callPrivateMethod($instance, 'formatFileSize', 1048576))->toBe('1 MB')
        ->and(callPrivateMethod($instance, 'formatFileSize', 1572864))->toBe('1.5 MB');
});

test('loadDiff sets isImage and imageData for image files', function () {
    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    Process::fake([
        'git show HEAD:"test.png" 2>/dev/null' => Process::result(output: $imageContent, exitCode: 0),
    ]);

    // Create temp file
    $tempFile = sys_get_temp_dir().'/test.png';
    file_put_contents($tempFile, $imageContent);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => sys_get_temp_dir()]);

    $component->call('loadDiff', 'test.png', false);

    expect($component->get('isImage'))->toBeTrue()
        ->and($component->get('isEmpty'))->toBeFalse()
        ->and($component->get('isBinary'))->toBeFalse()
        ->and($component->get('isLargeFile'))->toBeFalse()
        ->and($component->get('imageData'))->toBeArray()
        ->and($component->get('diffData'))->toBeNull()
        ->and($component->get('files'))->toBeNull();

    unlink($tempFile);
});

test('loadDiff does not treat non-image files as images', function () {
    Process::fake([
        'git diff --no-color --no-ext-diff --unified=3 -- "test.txt"' => Process::result(
            output: "diff --git a/test.txt b/test.txt\nindex abc..def 100644\n--- a/test.txt\n+++ b/test.txt\n@@ -1 +1 @@\n-old\n+new\n",
            exitCode: 0
        ),
        'git cat-file -s HEAD:"test.txt" 2>/dev/null || echo 0' => Process::result(output: '100', exitCode: 0),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath]);

    $component->call('loadDiff', 'test.txt', false);

    expect($component->get('isImage'))->toBeFalse()
        ->and($component->get('imageData'))->toBeNull();
});

test('component renders image comparison for new image', function () {
    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    Process::fake([
        'git show HEAD:"new.png" 2>/dev/null' => Process::result(output: '', exitCode: 1),
    ]);

    $tempFile = sys_get_temp_dir().'/new.png';
    file_put_contents($tempFile, $imageContent);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => sys_get_temp_dir()])
        ->call('loadDiff', 'new.png', false);

    $component->assertSee('NEW')
        ->assertSee('data:image/png;base64,', false);

    unlink($tempFile);
});

test('component renders image comparison for modified image', function () {
    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    Process::fake([
        'git show HEAD:"modified.png" 2>/dev/null' => Process::result(output: $imageContent, exitCode: 0),
    ]);

    $tempFile = sys_get_temp_dir().'/modified.png';
    file_put_contents($tempFile, $imageContent);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => sys_get_temp_dir()])
        ->call('loadDiff', 'modified.png', false);

    $component->assertSee('MODIFIED')
        ->assertSee('Side by Side')
        ->assertSee('Slider')
        ->assertSee('Before')
        ->assertSee('After');

    unlink($tempFile);
});

test('component renders image comparison for deleted image', function () {
    $imageContent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');

    Process::fake([
        'git show HEAD:"deleted.png" 2>/dev/null' => Process::result(output: $imageContent, exitCode: 0),
    ]);

    $component = Livewire::test(DiffViewer::class, ['repoPath' => $this->repoPath])
        ->call('loadDiff', 'deleted.png', false);

    $component->assertSee('DELETED')
        ->assertSee('data:image/png;base64,', false);
});
