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
                'fetch-all', 'force-push', 'create-branch', 'select-all',
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
                'icon' => 'phosphor-plus',
            ],
            [
                'id' => 'unstage-all',
                'label' => 'Unstage All',
                'shortcut' => '⌘⇧U',
                'event' => 'keyboard-unstage-all',
                'keywords' => ['unstage', 'remove', 'all', 'reset'],
                'requiresInput' => false,
                'icon' => 'phosphor-minus',
            ],
            [
                'id' => 'discard-all',
                'label' => 'Discard All',
                'shortcut' => null,
                'event' => 'palette-discard-all',
                'keywords' => ['discard', 'revert', 'all', 'checkout'],
                'requiresInput' => false,
                'icon' => 'phosphor-trash',
            ],
            [
                'id' => 'stash-all',
                'label' => 'Stash All',
                'shortcut' => '⌘⇧S',
                'event' => 'keyboard-stash',
                'keywords' => ['stash', 'save', 'shelve'],
                'requiresInput' => false,
                'icon' => 'phosphor-archive',
            ],
            [
                'id' => 'toggle-view',
                'label' => 'Toggle File View',
                'shortcut' => null,
                'event' => 'palette-toggle-view',
                'keywords' => ['view', 'tree', 'flat', 'list'],
                'requiresInput' => false,
                'icon' => 'phosphor-list-bullets',
            ],
            [
                'id' => 'commit',
                'label' => 'Commit',
                'shortcut' => '⌘↵',
                'event' => 'keyboard-commit',
                'keywords' => ['commit', 'save', 'check in'],
                'requiresInput' => false,
                'icon' => 'phosphor-check',
            ],
            [
                'id' => 'commit-push',
                'label' => 'Commit and Push',
                'shortcut' => '⌘⇧↵',
                'event' => 'keyboard-commit-push',
                'keywords' => ['commit', 'push', 'save', 'upload'],
                'requiresInput' => false,
                'icon' => 'phosphor-check-circle',
            ],
            [
                'id' => 'toggle-amend',
                'label' => 'Toggle Amend',
                'shortcut' => null,
                'event' => 'palette-toggle-amend',
                'keywords' => ['amend', 'edit', 'last commit'],
                'requiresInput' => false,
                'icon' => 'phosphor-pencil-simple',
            ],
            [
                'id' => 'push',
                'label' => 'Push',
                'shortcut' => null,
                'event' => 'palette-push',
                'keywords' => ['push', 'upload', 'send'],
                'requiresInput' => false,
                'icon' => 'phosphor-arrow-up',
            ],
            [
                'id' => 'pull',
                'label' => 'Pull',
                'shortcut' => null,
                'event' => 'palette-pull',
                'keywords' => ['pull', 'download', 'receive'],
                'requiresInput' => false,
                'icon' => 'phosphor-arrow-down',
            ],
            [
                'id' => 'fetch',
                'label' => 'Fetch',
                'shortcut' => null,
                'event' => 'palette-fetch',
                'keywords' => ['fetch', 'refresh', 'update'],
                'requiresInput' => false,
                'icon' => 'phosphor-arrows-clockwise',
            ],
            [
                'id' => 'fetch-all',
                'label' => 'Fetch All Remotes',
                'shortcut' => null,
                'event' => 'palette-fetch-all',
                'keywords' => ['fetch', 'all', 'remotes', 'update'],
                'requiresInput' => false,
                'icon' => 'phosphor-cloud-arrow-down',
            ],
            [
                'id' => 'force-push',
                'label' => 'Force Push (with Lease)',
                'shortcut' => null,
                'event' => 'palette-force-push',
                'keywords' => ['force', 'push', 'lease', 'overwrite'],
                'requiresInput' => false,
                'icon' => 'phosphor-arrow-fat-up',
            ],
            [
                'id' => 'create-branch',
                'label' => 'Create Branch',
                'shortcut' => null,
                'event' => null,
                'keywords' => ['branch', 'create', 'new', 'checkout -b'],
                'requiresInput' => true,
                'icon' => 'phosphor-git-branch',
            ],
            [
                'id' => 'toggle-sidebar',
                'label' => 'Toggle Sidebar',
                'shortcut' => '⌘B',
                'event' => 'palette-toggle-sidebar',
                'keywords' => ['sidebar', 'panel', 'toggle', 'hide'],
                'requiresInput' => false,
                'icon' => 'phosphor-sidebar-simple',
            ],
            [
                'id' => 'open-settings',
                'label' => 'Open Settings',
                'shortcut' => null,
                'event' => 'open-settings',
                'keywords' => ['settings', 'preferences', 'config'],
                'requiresInput' => false,
                'icon' => 'phosphor-gear',
            ],
            [
                'id' => 'open-folder',
                'label' => 'Open Repository…',
                'shortcut' => null,
                'event' => 'palette-open-folder',
                'keywords' => ['open', 'folder', 'repository', 'browse'],
                'requiresInput' => false,
                'icon' => 'phosphor-folder-open',
            ],
            [
                'id' => 'select-all',
                'label' => 'Select All Files',
                'shortcut' => '⌘A',
                'event' => 'keyboard-select-all',
                'keywords' => ['select', 'all', 'files'],
                'requiresInput' => false,
                'icon' => 'phosphor-selection-all',
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
            $this->inputValue = '';
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
