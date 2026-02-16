<?php

declare(strict_types=1);

namespace App\Livewire;

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

    #[On('open-command-palette')]
    public function open(): void
    {
        $this->isOpen = true;
        $this->mode = 'search';
        $this->query = '';
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

    public function render()
    {
        return view('livewire.command-palette');
    }
}
