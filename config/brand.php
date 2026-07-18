<?php

return [
    'company_name' => $_ENV['APP_COMPANY_NAME'] ?? 'Beetle Analytics',
    'tagline'       => $_ENV['APP_TAGLINE'] ?? 'Restaurant Performance Dashboard',
    'colors' => [
        'navy'          => '#1E2A3A',
        'navy_deep'     => '#141C27',
        'orange'        => '#F26B3A',
        'teal'          => '#0E7C7B',
        'ink'           => '#1E293B',
        'muted'         => '#64748B',
        'card_tint'     => '#F4F6F8',
        'card_tint_warm'=> '#FDF1EC',
    ],
];
