<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Process;

class GitConfigValidator
{
    public function __construct(
        protected string $repoPath,
    ) {
        $gitDir = rtrim($this->repoPath, '/') . '/.git';
        if (! is_dir($gitDir)) {
            throw new \InvalidArgumentException("Not a valid git repository: {$this->repoPath}");
        }
    }

    public function validate(): array
    {
        $issues = [];

        $userName = $this->getConfig('user.name');
        if (empty($userName)) {
            $issues[] = 'Git user.name is not configured';
        }

        $userEmail = $this->getConfig('user.email');
        if (empty($userEmail)) {
            $issues[] = 'Git user.email is not configured';
        }

        $gitVersion = $this->getGitVersion();
        if (version_compare($gitVersion, '2.0.0', '<')) {
            $issues[] = "Git version {$gitVersion} is too old (minimum 2.0.0 required)";
        }

        return $issues;
    }

    protected function getConfig(string $key): string
    {
        $result = Process::path($this->repoPath)->run("git config {$key}");

        return trim($result->output());
    }

    protected function getGitVersion(): string
    {
        $result = Process::run('git --version');
        $output = trim($result->output());

        if (preg_match('/git version (\d+\.\d+\.\d+)/', $output, $matches)) {
            return $matches[1];
        }

        return '0.0.0';
    }
}
