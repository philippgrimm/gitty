<?php

declare(strict_types=1);

namespace App\Exceptions;

class InvalidRepositoryException extends \InvalidArgumentException
{
    public function __construct(string $repoPath)
    {
        parent::__construct("Not a valid git repository: {$repoPath}");
    }
}
