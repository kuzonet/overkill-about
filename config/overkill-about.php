<?php

// config for Kuzonet/OverkillAbout

use Composer\InstalledVersions;
use Illuminate\Support\Facades\DB;

return [

    /*
    |--------------------------------------------------------------------------
    | DEMO CONFIG: review before use
    |--------------------------------------------------------------------------
    |
    | The entries below are sample content for demonstration purposes only.
    | They show placeholder contact details, links and technical information
    | (PHP, Laravel and database versions, installed packages). Review and
    | replace every entry before going live, and limit technical details to
    | trusted users with `enabled` (a Gate ability) or remove them.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | About screens, keyed by Filament panel id or screen name
    |--------------------------------------------------------------------------
    |
    | Each panel that registers OverkillAboutPlugin::make() reads its own entry
    | here. OverkillAboutPlugin::make('support') adds another menu item that
    | reads the 'support' entry instead, so one panel can show several modals.
    | A panel with no entry shows the About item with just the app name.
    |
    | The modal shows one section per entry in `sections`, in order:
    |
    |   'sections' => [
    |       'support_info' => [             // key → title fallback "Support Info"
    |           'enabled' => true,          // optional, see "enabled" below
    |           'title' => 'Support',       // optional
    |           'icon' => 'heroicon-o-…',   // optional
    |           'description' => '…',       // optional
    |           'collapsible' => false,     // optional
    |           'collapsed' => false,       // optional
    |           'items' => [
    |               'Email' => [            // key → label fallback "Email"
    |                   'enabled' => true,  // optional, see "enabled" below
    |                   'title' => '…',     // optional
    |                   'value' => '…',
    |                   'icon' => '…',      // optional
    |                   'url' => '…',       // optional: http(s), mailto, tel or /path
    |                   'new_tab' => true,  // optional, default true for http(s) links
    |               ],
    |               'Seats' => '50',        // shorthand: label => value
    |           ],
    |       ],
    |   ],
    |
    | `enabled` (panel, section or item) is true/false, or a Gate ability name
    | (or a list of abilities, all required) checked for the signed-in user
    | when the modal opens, e.g. 'enabled' => 'viewTechnicalDetails'. Define
    | the ability in your app; an undefined ability hides the part (unless a
    | Gate::before() callback returns true, e.g. a super-admin check).
    |
    | Text can use {user.<attribute>} and {tenant.<attribute>} placeholders,
    | filled in for the signed-in user and current Filament tenant when the
    | modal opens: 'value' => '{user.name}', 'url' => 'mailto:{user.email}',
    | 'title' => '{tenant.name}'. Hidden model attributes (password, …) are
    | never shown. Don't call auth() in this file: config loads before the
    | user is known.
    |
    | An item whose value is null or blank still shows its label with an empty
    | value; sections without items are left out. Values are worked
    | out when the config loads (and frozen by `php artisan config:cache`).
    |
    | This file is required on every boot — every request and every artisan
    | command — so nothing in it may throw. Wrap anything that can fail (a DB
    | query, a lookup that might not resolve) in try/catch and return null on
    | failure, as the items below do.
    |
    | `modal.layout` picks the look: 'standard' (default) shows each section as
    | a callout; 'component' shows Filament sections, which also render the
    | section `description` and honour `collapsible` / `collapsed`.
    |
    | `admin` below shows every option; `app` is a smaller support-style example.
    |
    */

    'admin' => [

        // Show the user menu item on this panel: true/false or a Gate ability
        'enabled' => true,

        // The user menu item
        'menu' => [
            'label' => null,                // null uses the translated "About"
            'icon' => 'heroicon-o-information-circle',
            'sort' => null,                 // position within the user menu
        ],

        // The modal
        'modal' => [
            'heading' => null,              // null uses "About {app name}"
            'description' => null,
            'width' => '2xl',                // Filament\Support\Enums\Width value: sm, md, lg, xl, 2xl …
            'slide_over' => false,
            'layout' => 'standard',         // 'standard' (callouts) or 'component' (collapsible sections)
        ],

        'sections' => [

            'application' => [
                'title' => 'App Details',
                'icon' => 'heroicon-o-cube',
                'items' => [
                    'Name' => [
                        'title' => 'Application Name',
                        'value' => env('APP_NAME', 'Laravel'),
                    ],
                    'Version' => [
                        'title' => 'Application Version',
                        'value' => env('APP_VERSION'),
                    ],
                    'Environment' => [
                        'title' => 'Application Environment',
                        'value' => env('APP_ENV'),
                    ],
                    'Mode' => [
                        'title' => 'Application Mode',
                        'value' => env('APP_DEBUG') ? 'Debug' : 'Production',
                    ],
                    'User' => [
                        'title' => 'User',
                        'value' => '{user.name}',
                    ],
                    'Tenant' => [
                        'title' => 'Tenant',
                        'value' => '{tenant.name}',
                    ],

                ],
            ],

            'technicals' => [
                'enabled' => true,         // or a Gate ability, e.g. 'viewTechnicalDetails'
                'title' => 'Technical Details',
                'icon' => 'heroicon-o-cog',
                'description' => 'Application & system technical information.',
                'collapsible' => true,
                'collapsed' => true,
                'items' => [
                    'Laravel' => [
                        'title' => 'Laravel Version',
                        'value' => app()->version(),
                    ],
                    'Filament' => [
                        'title' => 'Filament Version',
                        // Wrapped: this config is required on every boot, so nothing in it may
                        // throw — a missing/renamed package would otherwise break every request.
                        'value' => (function () {
                            try {
                                return InstalledVersions::getPrettyVersion('filament/filament');
                            } catch (Throwable) {
                                return null;
                            }
                        })(),
                    ],
                    'PHP' => [
                        'title' => 'PHP Version',
                        'value' => PHP_VERSION,
                    ],
                    // 'OS' => PHP_OS_FAMILY,  // shorthand item
                    'OS Version' => [
                        'title' => 'OS Version',
                        'value' => php_uname('s') . ' ' . php_uname('r'),
                    ],
                    'Database Connection' => [
                        'title' => 'Database Connection',
                        'value' => env('DB_CONNECTION'),
                    ],
                    'Database Version' => [
                        'title' => 'Database Version',
                        // Wrapped: this config is required on every boot (every request, every
                        // artisan command), so a DB query that fails — wrong driver, DB down,
                        // no connection configured yet — must not break the boot.
                        'value' => (function () {
                            try {
                                return env('DB_CONNECTION') === 'sqlite'
                                    ? DB::selectOne('select sqlite_version() as version')->version
                                    : DB::selectOne('select version() as version')->version;
                            } catch (Throwable) {
                                return null;
                            }
                        })(),
                    ],
                    'Database Host' => [
                        'title' => 'Database Host',
                        'value' => env('DB_HOST'),
                    ],
                ],
            ],

        ],

    ],

    'app' => [

        'menu' => [
            'label' => 'Support',
            'icon' => 'heroicon-o-lifebuoy',
        ],

        'modal' => [
            'heading' => null,              // null uses "About {app name}"
            'description' => null,
            'width' => '2xl',                // Filament\Support\Enums\Width value: sm, md, lg, xl, 2xl …
            'slide_over' => true,
            'layout' => 'component',         // 'standard' (callouts) or 'component' (collapsible sections)
        ],

        'sections' => [

            'application' => [
                'title' => 'App Details',
                'icon' => 'heroicon-o-cube',
                'items' => [
                    'Name' => [
                        'title' => 'Application Name',
                        'value' => env('APP_NAME', 'Laravel'),
                    ],
                    'Version' => [
                        'title' => 'Application Version',
                        'value' => env('APP_VERSION'),
                    ],
                    'User' => [
                        'title' => 'User',
                        'value' => '{user.name}',
                    ],
                    'Tenant' => [
                        'title' => 'Tenant',
                        'value' => '{tenant.name}',
                    ],

                ],
            ],

            'contact' => [
                'title' => 'Contact Us',
                'icon' => 'heroicon-o-phone',
                'items' => [
                    'Email' => [
                        'value' => 'support@example.com',
                        'icon' => 'heroicon-o-envelope',
                        'url' => 'mailto:support@example.com',
                    ],
                    'Phone' => [
                        'value' => '+1 (555) 010-0000',
                        'icon' => 'heroicon-o-phone',
                        'url' => 'tel:+15550100000',
                    ],
                    'Knowledge Base' => [
                        'value' => 'Guides and answers',
                        'icon' => 'heroicon-o-book-open',
                        'url' => 'https://support.example.com/kb',
                    ],
                ],
            ],

        ],

    ],

];
