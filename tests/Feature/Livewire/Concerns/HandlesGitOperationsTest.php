<?php

declare(strict_types=1);

use App\Livewire\Concerns\HandlesGitOperations;
use Livewire\Component;
use Livewire\Livewire;

class TestGitOperationsComponent extends Component
{
    use HandlesGitOperations;

    public string $error = '';

    public string $lastResult = '';

    public function successfulOperation(): void
    {
        $this->executeGitOperation(function () {
            $this->lastResult = 'success';
        });
    }

    public function failingOperation(): void
    {
        $this->executeGitOperation(function () {
            throw new \RuntimeException('fatal: not a git repository');
        });
    }

    public function operationWithReturn(): string
    {
        return $this->executeGitOperation(function () {
            return 'returned-value';
        }) ?? '';
    }

    public function operationWithoutStatusUpdate(): void
    {
        $this->executeGitOperation(function () {
            $this->lastResult = 'no-status-update';
        }, dispatchStatusUpdate: false);
    }

    public function render(): string
    {
        return '<div>Test Component</div>';
    }
}

test('executeGitOperation calls callback on success', function () {
    Livewire::test(TestGitOperationsComponent::class)
        ->call('successfulOperation')
        ->assertSet('lastResult', 'success')
        ->assertSet('error', '');
});

test('executeGitOperation dispatches status-updated on success by default', function () {
    Livewire::test(TestGitOperationsComponent::class)
        ->call('successfulOperation')
        ->assertDispatched('status-updated');
});

test('executeGitOperation does not dispatch status-updated when disabled', function () {
    Livewire::test(TestGitOperationsComponent::class)
        ->call('operationWithoutStatusUpdate')
        ->assertSet('lastResult', 'no-status-update')
        ->assertNotDispatched('status-updated');
});

test('executeGitOperation sets error on failure', function () {
    Livewire::test(TestGitOperationsComponent::class)
        ->call('failingOperation')
        ->assertSet('error', 'This folder is not a git repository');
});

test('executeGitOperation dispatches show-error on failure', function () {
    Livewire::test(TestGitOperationsComponent::class)
        ->call('failingOperation')
        ->assertDispatched('show-error');
});

test('executeGitOperation clears previous error on success', function () {
    Livewire::test(TestGitOperationsComponent::class)
        ->call('failingOperation')
        ->assertSet('error', 'This folder is not a git repository')
        ->call('successfulOperation')
        ->assertSet('error', '');
});
