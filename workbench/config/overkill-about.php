<?php

// Test-only config, loaded by Testbench's workbench (see testbench.yaml) in place of the
// package's own config/overkill-about.php for the whole test suite. It is deliberately static —
// no DB, env() or installed-version calls — so every test boots against known, safe values.
// Individual tests still override whatever they need with config()->set('overkill-about...'); this
// is just the baseline they override from, and a place for a few different permutations.

return [

    'admin' => [
        'enabled' => true,

        'menu' => [
            'label' => null,
            'icon' => 'heroicon-o-information-circle',
            'sort' => null,
        ],

        'modal' => [
            'heading' => null,
            'description' => null,
            'width' => 'lg',
            'slide_over' => false,
        ],

        'sections' => [

            'application' => [
                'title' => 'App Details',
                'icon' => 'heroicon-o-cube',
                'items' => [
                    'Name' => 'Workbench App',
                    'Version' => '1.0.0',
                    'User' => '{user.name}',
                    'Tenant' => '{tenant.name}',
                ],
            ],

            'contact' => [
                'icon' => 'heroicon-o-phone',
                'items' => [
                    'Email' => [
                        'value' => 'support@example.com',
                        'icon' => 'heroicon-o-envelope',
                        'url' => 'mailto:support@example.com',
                    ],
                ],
            ],

            // Gate-gated permutation: hidden unless the test defines the ability.
            'restricted' => [
                'enabled' => 'viewTechnicalDetails',
                'title' => 'Restricted',
                'items' => [
                    'Secret' => 'only-for-admins',
                ],
            ],

        ],
    ],

    'app' => [

        'menu' => [
            'label' => 'Support',
            'icon' => 'heroicon-o-lifebuoy',
        ],

        'sections' => [

            'support' => [
                'items' => [
                    'Knowledge Base' => [
                        'value' => 'kb.example.com',
                        'url' => 'https://support.example.com/kb',
                    ],
                ],
            ],

        ],
    ],

];
