<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Process;

class GitConfigValidator extends AbstractGitService
{
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
        if (version_compare($gitVersion, '2.30.0', '<')) {
            $issues[] = "Git version {$gitVersion} is too old (minimum 2.30.0 required)";
        }

        return $issues;
    }

    public static function checkGitBinary(): bool
    {
        $result = Process::run('which git');

        return $result->exitCode() === 0;
    }

    public function validateAll(): array
    {
        if (! self::checkGitBinary()) {
            return ['Git is not installed or not in PATH'];
        }

        return $this->validate();
    }

    protected function getConfig(string $key): string
    {
        $result = $this->commandRunner->run('config', [$key]);

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
