<?php

declare(strict_types=1);

namespace App\Exceptions;

class GitCommandFailedException extends \RuntimeException
{
    public function __construct(string $command, string $errorOutput = '', int $exitCode = 1)
    {
        $message = $errorOutput !== ''
            ? "{$command}: {$errorOutput}"
            : "Git command failed: {$command}";

        parent::__construct($message, $exitCode);
    }
}
