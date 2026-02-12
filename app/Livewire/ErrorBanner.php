<?php

declare(strict_types=1);

namespace App\Livewire;

use Livewire\Attributes\On;
use Livewire\Component;

class ErrorBanner extends Component
{
    public bool $visible = false;

    public string $message = '';

    public string $type = 'error';

    public bool $persistent = false;

    #[On('show-error')]
    public function showError(string $message, string $type = 'error', bool $persistent = false): void
    {
        $this->message = $message;
        $this->type = $type;
        $this->persistent = $persistent;
        $this->visible = true;
    }

    public function dismiss(): void
    {
        $this->visible = false;
    }

    public function render()
    {
        return view('livewire.error-banner');
    }
}
