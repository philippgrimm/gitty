<?php

declare(strict_types=1);

namespace App\Services;

use Native\Desktop\Facades\Notification;

class NotificationService
{
    public function __construct(
        private readonly SettingsService $settingsService
    ) {}

    public function notify(string $title, string $body): void
    {
        if (! $this->settingsService->get('notifications_enabled', true)) {
            return;
        }

        try {
            if (! class_exists(Notification::class)) {
                return;
            }

            Notification::new()
                ->title($title)
                ->message($body)
                ->show();
        } catch (\Exception $e) {
            // Gracefully degrade if NativePHP notifications are unavailable
            return;
        }
    }
}
