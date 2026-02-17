<?php

declare(strict_types=1);

namespace App\Exceptions;

class GitConflictException extends \RuntimeException
{
    public function __construct(string $operation = 'rebase')
    {
        parent::__construct("{$operation} failed due to conflicts. Resolve conflicts and continue.");
    }
}
