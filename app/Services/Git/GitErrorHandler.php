<?php

declare(strict_types=1);

namespace App\Services\Git;

class GitErrorHandler
{
    /**
     * Translate git error messages to user-friendly messages.
     */
    public static function translate(string $gitError): string
    {
        if (empty($gitError)) {
            return '';
        }

        // Not a git repository
        if (str_contains($gitError, 'fatal: not a git repository')) {
            return 'This folder is not a git repository';
        }

        // File not found
        if (str_contains($gitError, "error: pathspec") && str_contains($gitError, "did not match")) {
            return 'File not found in repository';
        }

        // Merge conflict
        if (str_contains($gitError, 'CONFLICT')) {
            return 'Merge conflict detected. Resolve conflicts in external editor.';
        }

        // Push rejected
        if (str_contains($gitError, 'rejected')) {
            return 'Push rejected. Pull remote changes first.';
        }

        // Authentication failed
        if (str_contains($gitError, 'Authentication failed') || str_contains($gitError, 'could not read Username')) {
            return 'Authentication failed. Check your credentials.';
        }

        // Git not installed
        if (str_contains($gitError, 'git: command not found') || str_contains($gitError, 'git: No such file')) {
            return 'Git is not installed. Please install git.';
        }

        // Corrupted repository
        if (str_contains($gitError, 'fatal: bad object') || str_contains($gitError, 'fatal: loose object')) {
            return "Repository may be corrupted. Try running 'git fsck'.";
        }

        // Return original error if no pattern matches
        return $gitError;
    }
}
