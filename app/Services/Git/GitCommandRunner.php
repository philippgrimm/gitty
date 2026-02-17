<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Support\Facades\Process;

class GitCommandRunner
{
    public function __construct(
        protected string $repoPath,
    ) {}

    /**
     * Run a git command with properly escaped arguments.
     *
     * @param  string  $subcommand  The git subcommand (e.g., 'status', 'add', 'commit')
     * @param  array<string>  $args  Arguments to escape and append
     */
    public function run(string $subcommand, array $args = []): ProcessResult
    {
        $command = $this->buildCommand($subcommand, $args);

        return Process::path($this->repoPath)->run($command);
    }

    /**
     * Run a git command and throw on failure.
     *
     * @param  string  $subcommand  The git subcommand
     * @param  array<string>  $args  Arguments to escape and append
     * @param  string  $errorPrefix  Prefix for the exception message
     *
     * @throws \RuntimeException
     */
    public function runOrFail(string $subcommand, array $args = [], string $errorPrefix = ''): ProcessResult
    {
        $result = $this->run($subcommand, $args);

        if (! $result->successful()) {
            $message = $errorPrefix !== ''
                ? $errorPrefix.': '.$result->errorOutput()
                : 'Git command failed: '.$result->errorOutput();

            throw new \RuntimeException($message);
        }

        return $result;
    }

    /**
     * Run a git command with stdin input (e.g., for git apply).
     *
     * @param  string  $subcommand  The git subcommand (may include flags like 'apply --cached')
     * @param  string  $input  The stdin input
     */
    public function runWithInput(string $subcommand, string $input): ProcessResult
    {
        $command = "git {$subcommand}";

        return Process::path($this->repoPath)->input($input)->run($command);
    }

    /**
     * Build the full git command string with escaped arguments.
     *
     * @param  array<string>  $args
     */
    private function buildCommand(string $subcommand, array $args): string
    {
        $command = "git {$subcommand}";

        if (! empty($args)) {
            $escapedArgs = array_map('escapeshellarg', $args);
            $command .= ' '.implode(' ', $escapedArgs);
        }

        return $command;
    }
}
