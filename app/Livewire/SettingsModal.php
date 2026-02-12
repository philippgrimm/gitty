<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Services\SettingsService;
use Livewire\Attributes\On;
use Livewire\Component;

class SettingsModal extends Component
{
    public int $autoFetchInterval;

    public string $externalEditor;

    public string $theme;

    public string $defaultBranch;

    public bool $confirmDiscard;

    public bool $confirmForcePush;

    public bool $showUntracked;

    public int $diffContextLines;

    public bool $showModal = false;

    public function mount(): void
    {
        $this->loadSettings();
    }

    #[On('open-settings')]
    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function save(): void
    {
        $service = new SettingsService();

        $service->set('auto_fetch_interval', $this->autoFetchInterval);
        $service->set('external_editor', $this->externalEditor);
        $service->set('theme', $this->theme);
        $service->set('default_branch', $this->defaultBranch);
        $service->set('confirm_discard', $this->confirmDiscard);
        $service->set('confirm_force_push', $this->confirmForcePush);
        $service->set('show_untracked', $this->showUntracked);
        $service->set('diff_context_lines', $this->diffContextLines);

        $this->dispatch('settings-updated');
        $this->closeModal();
    }

    public function resetToDefaults(): void
    {
        $service = new SettingsService();
        $service->reset();

        $this->loadSettings();
    }

    public function render()
    {
        return view('livewire.settings-modal');
    }

    private function loadSettings(): void
    {
        $service = new SettingsService();

        $this->autoFetchInterval = $service->get('auto_fetch_interval');
        $this->externalEditor = $service->get('external_editor');
        $this->theme = $service->get('theme');
        $this->defaultBranch = $service->get('default_branch');
        $this->confirmDiscard = $service->get('confirm_discard');
        $this->confirmForcePush = $service->get('confirm_force_push');
        $this->showUntracked = $service->get('show_untracked');
        $this->diffContextLines = $service->get('diff_context_lines');
    }
}
