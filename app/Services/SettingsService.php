<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Setting;

class SettingsService
{
    private const BOOLEAN_SETTINGS = [
        'confirm_discard',
        'confirm_force_push',
        'show_untracked',
        'notifications_enabled',
    ];

    private const DEFAULTS = [
        'auto_fetch_interval' => 180,
        'external_editor' => '',
        'theme' => 'dark',
        'default_branch' => 'main',
        'confirm_discard' => true,
        'confirm_force_push' => true,
        'show_untracked' => true,
        'diff_context_lines' => 3,
        'notifications_enabled' => true,
    ];

    public function defaults(): array
    {
        return self::DEFAULTS;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $setting = Setting::where('key', $key)->first();

        if ($setting === null) {
            return $default ?? self::DEFAULTS[$key] ?? $default;
        }

        return $this->castValue($key, $setting->value);
    }

    public function set(string $key, mixed $value): void
    {
        $storedValue = $this->prepareForStorage($value);

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue]
        );
    }

    public function all(): array
    {
        $stored = Setting::all()->pluck('value', 'key')->toArray();

        $merged = array_merge(self::DEFAULTS, $stored);

        $result = [];
        foreach ($merged as $key => $value) {
            $result[$key] = $this->castValue($key, $value);
        }

        return $result;
    }

    public function reset(): void
    {
        Setting::query()->delete();
    }

    private function castValue(string $key, mixed $value): mixed
    {
        if (in_array($key, self::BOOLEAN_SETTINGS, true)) {
            return $value === true || $value === '1' || $value === 1;
        }

        if (is_numeric($value)) {
            return is_float($value + 0) ? (float) $value : (int) $value;
        }

        return $value;
    }

    private function prepareForStorage(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        return (string) $value;
    }

    /**
     * Get commit message history for a specific repository.
     *
     * @return array<int, string>
     */
    public function getCommitHistory(string $repoPath): array
    {
        $key = 'commit_history_'.md5($repoPath);
        $setting = Setting::where('key', $key)->first();

        if ($setting === null) {
            return [];
        }

        $decoded = json_decode($setting->value, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Add a commit message to the history for a specific repository.
     * Deduplicates and keeps max 20 messages.
     */
    public function addCommitMessage(string $repoPath, string $message): void
    {
        $key = 'commit_history_'.md5($repoPath);
        $history = $this->getCommitHistory($repoPath);

        // Remove duplicates (case-sensitive)
        $history = array_values(array_filter($history, fn ($msg) => $msg !== $message));

        // Prepend new message
        array_unshift($history, $message);

        // Keep max 20
        $history = array_slice($history, 0, 20);

        Setting::updateOrCreate(
            ['key' => $key],
            ['value' => json_encode($history)]
        );
    }
}
