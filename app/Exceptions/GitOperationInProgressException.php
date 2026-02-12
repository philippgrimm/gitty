<?php

declare(strict_types=1);

namespace App\Exceptions;

class GitOperationInProgressException extends \RuntimeException
{
    public function __construct(string $repoPath)
    {
        parent::__construct("A git operation is already in progress for repository: {$repoPath}");
    }
}
