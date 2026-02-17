<?php

declare(strict_types=1);

namespace App\Livewire\Concerns;

use App\Services\Git\GitErrorHandler;

trait HandlesGitOperations
{
    /**
     * Execute a git operation with standardized error handling.
     *
     * Wraps the operation in try/catch, translates errors via GitErrorHandler,
     * and dispatches appropriate Livewire events.
     *
     * @param  callable  $operation  The git operation to execute
     * @param  bool  $dispatchStatusUpdate  Whether to dispatch 'status-updated' on success
     * @return mixed The return value of the operation, or null on failure
     */
    protected function executeGitOperation(callable $operation, bool $dispatchStatusUpdate = true): mixed
    {
        try {
            $result = $operation();
            $this->error = '';

            if ($dispatchStatusUpdate) {
                $this->dispatch('status-updated');
            }

            return $result;
        } catch (\Exception $e) {
            $this->error = GitErrorHandler::translate($e->getMessage());
            $this->dispatch('show-error', message: $this->error, type: 'error', persistent: false);

            return null;
        }
    }
}
