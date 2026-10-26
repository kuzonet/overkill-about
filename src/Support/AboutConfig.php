<?php

namespace Kuzonet\OverkillAbout\Support;

class AboutConfig
{
    /**
     * The about screen for a panel: `overkill-about.<key>`, or an empty screen when not configured.
     *
     * @return array<string, mixed>
     */
    public static function forPanel(?string $key): array
    {
        $config = config('overkill-about');

        if (blank($key) || ! is_array($config) || ! is_array($config[$key] ?? null)) {
            return [];
        }

        return $config[$key];
    }
}
