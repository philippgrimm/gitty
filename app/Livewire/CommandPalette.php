<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class CommandPalette extends Component
{
    public bool $isOpen = false;

    public string $mode = 'search';

    public string $query = '';

    public string $inputValue = '';

    public ?string $inputCommand = null;

    public ?string $inputError = null;

    public string $repoPath = '';

    public int $stagedCount = 0;

    #[On('open-command-palette')]
    public function open(): void
    {
        $this->isOpen = true;
        $this->mode = 'search';
        $this->query = '';
    }

    #[On('toggle-command-palette')]
    public function toggle(): void
    {
        if ($this->isOpen) {
            $this->close();
        } else {
            $this->open();
        }
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->mode = 'search';
        $this->query = '';
        $this->inputValue = '';
        $this->inputCommand = null;
        $this->inputError = null;
    }

    #[On('status-updated')]
    public function handleStatusUpdated(int $stagedCount = 0, array $aheadBehind = []): void
    {
        $this->stagedCount = $stagedCount;
    }

    #[On('repo-switched')]
    public function handleRepoSwitched(string $path): void
    {
        $this->repoPath = $path;
    }

    public function getDisabledCommands(): array
    {
        $disabled = [];

        if (empty($this->repoPath)) {
            $disabled = array_fill_keys([
                'stage-all', 'unstage-all', 'discard-all', 'stash-all', 'toggle-view',
                'commit', 'commit-push', 'toggle-amend', 'push', 'pull', 'fetch',
                'fetch-all', 'force-push', 'create-branch', 'select-all', 'toggle-history',
                'abort-merge', 'create-tag', 'toggle-diff-view', 'open-in-editor',
                'continue-rebase', 'abort-rebase', 'focus-commit',
            ], true);
        }

        if ($this->stagedCount === 0) {
            $disabled['commit'] = true;
            $disabled['commit-push'] = true;
        }

        return $disabled;
    }

    public static function getCommands(): array
    {
        return [
            [
                'id' => 'stage-all',
                'label' => 'Stage All',
                'shortcut' => '⌘⇧K',
                'event' => 'keyboard-stage-all',
                'keywords' => ['stage', 'add', 'all', 'git add'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-plus',
            ],
            [
                'id' => 'unstage-all',
                'label' => 'Unstage All',
                'shortcut' => '⌘⇧U',
                'event' => 'keyboard-unstage-all',
                'keywords' => ['unstage', 'remove', 'all', 'reset'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-minus',
            ],
            [
                'id' => 'discard-all',
                'label' => 'Discard All',
                'shortcut' => null,
                'event' => 'palette-discard-all',
                'keywords' => ['discard', 'revert', 'all', 'checkout'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-trash',
            ],
            [
                'id' => 'stash-all',
                'label' => 'Stash All',
                'shortcut' => '⌘⇧S',
                'event' => 'keyboard-stash',
                'keywords' => ['stash', 'save', 'shelve'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-archive',
            ],
            [
                'id' => 'toggle-view',
                'label' => 'Toggle File View',
                'shortcut' => null,
                'event' => 'palette-toggle-view',
                'keywords' => ['view', 'tree', 'flat', 'list'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-bulletlist',
            ],
            [
                'id' => 'commit',
                'label' => 'Commit',
                'shortcut' => '⌘↵',
                'event' => 'keyboard-commit',
                'keywords' => ['commit', 'save', 'check in'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-check',
            ],
            [
                'id' => 'commit-push',
                'label' => 'Commit and Push',
                'shortcut' => '⌘⇧↵',
                'event' => 'keyboard-commit-push',
                'keywords' => ['commit', 'push', 'save', 'upload'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-check-double',
            ],
            [
                'id' => 'toggle-amend',
                'label' => 'Toggle Amend',
                'shortcut' => null,
                'event' => 'palette-toggle-amend',
                'keywords' => ['amend', 'edit', 'last commit'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-edit',
            ],
            [
                'id' => 'undo-last-commit',
                'label' => 'Undo Last Commit',
                'shortcut' => '⌘Z',
                'event' => 'palette-undo-last-commit',
                'keywords' => ['undo', 'reset', 'last commit', 'revert'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-undo',
            ],
            [
                'id' => 'push',
                'label' => 'Push',
                'shortcut' => null,
                'event' => 'palette-push',
                'keywords' => ['push', 'upload', 'send'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-arrow-up',
            ],
            [
                'id' => 'pull',
                'label' => 'Pull',
                'shortcut' => null,
                'event' => 'palette-pull',
                'keywords' => ['pull', 'download', 'receive'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-arrow-down',
            ],
            [
                'id' => 'fetch',
                'label' => 'Fetch',
                'shortcut' => null,
                'event' => 'palette-fetch',
                'keywords' => ['fetch', 'refresh', 'update'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-sync',
            ],
            [
                'id' => 'fetch-all',
                'label' => 'Fetch All Remotes',
                'shortcut' => null,
                'event' => 'palette-fetch-all',
                'keywords' => ['fetch', 'all', 'remotes', 'update'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-cloud-download',
            ],
            [
                'id' => 'force-push',
                'label' => 'Force Push (with Lease)',
                'shortcut' => null,
                'event' => 'palette-force-push',
                'keywords' => ['force', 'push', 'lease', 'overwrite'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-arrow-up-box',
            ],
            [
                'id' => 'create-branch',
                'label' => 'Create Branch',
                'shortcut' => null,
                'event' => null,
                'keywords' => ['branch', 'create', 'new', 'checkout -b'],
                'requiresInput' => true,
                'icon' => 'pixelarticons-git-branch',
            ],
            [
                'id' => 'toggle-sidebar',
                'label' => 'Toggle Sidebar',
                'shortcut' => '⌘B',
                'event' => 'palette-toggle-sidebar',
                'keywords' => ['sidebar', 'panel', 'toggle', 'hide'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-layout-sidebar-left',
            ],
            [
                'id' => 'toggle-history',
                'label' => 'Toggle History',
                'shortcut' => '⌘H',
                'event' => 'toggle-history-panel',
                'keywords' => ['history', 'log', 'commits', 'toggle'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-clock',
            ],
            [
                'id' => 'show-shortcuts',
                'label' => 'Keyboard Shortcuts',
                'shortcut' => '⌘/',
                'event' => 'open-shortcut-help',
                'keywords' => ['shortcuts', 'keyboard', 'help', 'keys'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-keyboard',
            ],
            [
                'id' => 'open-settings',
                'label' => 'Open Settings',
                'shortcut' => null,
                'event' => 'open-settings',
                'keywords' => ['settings', 'preferences', 'config'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-sliders',
            ],
            [
                'id' => 'open-folder',
                'label' => 'Open Repository…',
                'shortcut' => null,
                'event' => 'palette-open-folder',
                'keywords' => ['open', 'folder', 'repository', 'browse'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-folder-plus',
            ],
            [
                'id' => 'toggle-diff-view',
                'label' => 'Toggle Diff View Mode',
                'shortcut' => null,
                'event' => 'palette-toggle-diff-view',
                'keywords' => ['diff', 'split', 'side', 'unified', 'view', 'columns'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-layout-columns',
            ],
            [
                'id' => 'abort-merge',
                'label' => 'Abort Merge',
                'shortcut' => null,
                'event' => 'palette-abort-merge',
                'keywords' => ['abort', 'merge', 'cancel', 'conflict'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-close-box',
            ],
            [
                'id' => 'create-tag',
                'label' => 'Create Tag',
                'shortcut' => null,
                'event' => 'palette-create-tag',
                'keywords' => ['tag', 'create', 'version', 'release'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-label',
            ],
            [
                'id' => 'open-in-editor',
                'label' => 'Open in Editor',
                'shortcut' => null,
                'event' => 'palette-open-in-editor',
                'keywords' => ['editor', 'open', 'code', 'vscode', 'cursor', 'sublime'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-code',
            ],
            [
                'id' => 'continue-rebase',
                'label' => 'Continue Rebase',
                'shortcut' => null,
                'event' => 'palette-continue-rebase',
                'keywords' => ['rebase', 'continue', 'resume'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-git-merge',
            ],
            [
                'id' => 'abort-rebase',
                'label' => 'Abort Rebase',
                'shortcut' => null,
                'event' => 'palette-abort-rebase',
                'keywords' => ['rebase', 'abort', 'cancel'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-close-box',
            ],
            [
                'id' => 'search',
                'label' => 'Search...',
                'shortcut' => '⌘F',
                'event' => 'open-search',
                'keywords' => ['search', 'find', 'commits', 'content', 'files'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-search',
            ],
            [
                'id' => 'focus-commit',
                'label' => 'Focus Commit Message',
                'shortcut' => '⌘L',
                'event' => 'focus-commit-message',
                'keywords' => ['focus', 'commit', 'message', 'textarea'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-art-text',
            ],
            [
                'id' => 'select-all',
                'label' => 'Select All Files',
                'shortcut' => '⌘A',
                'event' => 'keyboard-select-all',
                'keywords' => ['select', 'all', 'files'],
                'requiresInput' => false,
                'icon' => 'pixelarticons-checkbox-on',
            ],
        ];
    }

    #[Computed]
    public function filteredCommands(): array
    {
        $disabled = $this->getDisabledCommands();

        $commands = collect(self::getCommands())->map(function (array $command) use ($disabled): array {
            $command['disabled'] = isset($disabled[$command['id']]);

            return $command;
        });

        if (empty($this->query)) {
            return $commands->all();
        }

        $query = mb_strtolower($this->query);

        return $commands->filter(function (array $command) use ($query): bool {
            if (str_contains(mb_strtolower($command['label']), $query)) {
                return true;
            }

            foreach ($command['keywords'] as $keyword) {
                if (str_contains(mb_strtolower($keyword), $query)) {
                    return true;
                }
            }

            return false;
        })->values()->all();
    }

    public function executeCommand(string $commandId): void
    {
        $command = collect(self::getCommands())->firstWhere('id', $commandId);

        if (! $command) {
            return;
        }

        $disabled = $this->getDisabledCommands();
        if (isset($disabled[$commandId])) {
            return;
        }

        if ($command['requiresInput']) {
            $this->mode = 'input';
            $this->inputCommand = $commandId;
            $this->inputValue = $commandId === 'create-branch' ? 'feature/' : '';
            $this->inputError = null;

            return;
        }

        if ($command['event']) {
            $this->dispatch($command['event']);
        }

        $this->close();
    }

    public function submitInput(): void
    {
        if ($this->inputCommand === 'create-branch') {
            $name = trim($this->inputValue);

            if (empty($name)) {
                $this->inputError = 'Branch name is required';

                return;
            }

            if (str_contains($name, ' ')) {
                $this->inputError = 'Branch name cannot contain spaces';

                return;
            }

            $this->dispatch('palette-create-branch', name: $name);
            $this->close();
        }
    }

    #[On('open-command-palette-create-branch')]
    public function openCreateBranch(): void
    {
        $this->isOpen = true;
        $this->mode = 'input';
        $this->inputCommand = 'create-branch';
        $this->inputValue = 'feature/';
        $this->inputError = null;
    }

    public function cancelInput(): void
    {
        $this->mode = 'search';
        $this->inputCommand = null;
        $this->inputValue = '';
        $this->inputError = null;
    }

    public function render()
    {
        return view('livewire.command-palette');
    }
}
