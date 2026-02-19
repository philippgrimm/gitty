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
        if (str_contains($gitError, 'error: pathspec') && str_contains($gitError, 'did not match')) {
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

        // Uncommitted changes blocking checkout
        if (str_contains($gitError, 'error: Your local changes to the following files would be overwritten by checkout')
            || str_contains($gitError, 'Please commit your changes or stash them before you switch branches')) {
            return 'Cannot switch branches: You have uncommitted changes. Commit or stash them first.';
        }

        // Return original error if no pattern matches
        return $gitError;
    }

    /**
     * Check if the error message indicates uncommitted changes blocking checkout.
     *
     * @param  string  $errorMessage  The git error message to check
     * @return bool True if the error is due to uncommitted changes, false otherwise
     */
    public static function isDirtyTreeError(string $errorMessage): bool
    {
        return str_contains($errorMessage, 'error: Your local changes to the following files would be overwritten by checkout')
            || str_contains($errorMessage, 'Please commit your changes or stash them before you switch branches');
    }

    /**
     * Check if the error message indicates a branch is not fully merged.
     *
     * @param  string  $errorMessage  The git error message to check
     * @return bool True if the error is due to branch not being fully merged, false otherwise
     */
    public static function isNotFullyMergedError(string $errorMessage): bool
    {
        return str_contains($errorMessage, 'is not fully merged');
    }
}
