<?php

declare(strict_types=1);

namespace App\Services\Git;

use Illuminate\Support\Facades\Process;

class GitConfigValidator extends AbstractGitService
{
    private static ?bool $gitBinaryExists = null;

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
        if (self::$gitBinaryExists !== null) {
            return self::$gitBinaryExists;
        }

        $result = Process::run('which git');

        if ($result->exitCode() === 0) {
            self::$gitBinaryExists = true;

            return true;
        }

        return false;
    }

    /**
     * Reset the binary existence cache (intended for testing environments).
     */
    public static function resetCache(): void
    {
        self::$gitBinaryExists = null;
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
