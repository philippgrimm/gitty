<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Process;

class EditorService
{
    private const EDITORS = [
        'code' => ['name' => 'VS Code', 'command' => 'code', 'args' => '--goto {file}:{line}'],
        'cursor' => ['name' => 'Cursor', 'command' => 'cursor', 'args' => '--goto {file}:{line}'],
        'subl' => ['name' => 'Sublime Text', 'command' => 'subl', 'args' => '{file}:{line}'],
        'phpstorm' => ['name' => 'PhpStorm', 'command' => 'phpstorm', 'args' => '--line {line} {file}'],
        'zed' => ['name' => 'Zed', 'command' => 'zed', 'args' => '{file}:{line}'],
    ];

    public function __construct(
        private SettingsService $settings
    ) {}

    public function detectEditors(): array
    {
        $installed = [];

        foreach (self::EDITORS as $key => $editor) {
            $result = Process::run("which {$editor['command']}");

            if ($result->successful() && ! empty(trim($result->output()))) {
                $installed[$key] = $editor['name'];
            }
        }

        return $installed;
    }

    public function getDefaultEditor(): ?string
    {
        $saved = $this->settings->get('external_editor', '');

        if (! empty($saved) && isset(self::EDITORS[$saved])) {
            return $saved;
        }

        $installed = $this->detectEditors();

        return ! empty($installed) ? array_key_first($installed) : null;
    }

    public function openFile(string $repoPath, string $file, int $line = 1, ?string $editorKey = null): void
    {
        $editorKey = $editorKey ?? $this->getDefaultEditor();

        if ($editorKey === null || ! isset(self::EDITORS[$editorKey])) {
            throw new \RuntimeException('No editor configured or detected');
        }

        $editor = self::EDITORS[$editorKey];
        $fullPath = rtrim($repoPath, '/').'/'.ltrim($file, '/');

        $args = str_replace(
            ['{file}', '{line}'],
            [$fullPath, (string) $line],
            $editor['args']
        );

        $command = "{$editor['command']} {$args}";

        Process::run($command);
    }
}
