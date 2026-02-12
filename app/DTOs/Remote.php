<?php

declare(strict_types=1);

namespace App\DTOs;

readonly class Remote
{
    public function __construct(
        public string $name,
        public string $fetchUrl,
        public string $pushUrl,
    ) {}

    public static function fromRemoteLines(array $lines): array
    {
        $remotes = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $parts = preg_split('/\s+/', trim($line));
            $name = $parts[0] ?? '';
            $url = $parts[1] ?? '';
            $type = isset($parts[2]) ? trim($parts[2], '()') : '';

            if (! isset($remotes[$name])) {
                $remotes[$name] = [
                    'name' => $name,
                    'fetchUrl' => '',
                    'pushUrl' => '',
                ];
            }

            if ($type === 'fetch') {
                $remotes[$name]['fetchUrl'] = $url;
            } elseif ($type === 'push') {
                $remotes[$name]['pushUrl'] = $url;
            }
        }

        return array_map(
            fn ($remote) => new self($remote['name'], $remote['fetchUrl'], $remote['pushUrl']),
            array_values($remotes)
        );
    }
}
