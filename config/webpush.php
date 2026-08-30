<?php

return [
    'vapid' => [
        'subject' => env('VAPID_SUBJECT', 'mailto:admin@schedule.local'),
        'public_key' => env('VAPID_PUBLIC_KEY', 'BLwARxWefSksfKn5gOmwMqqqtCAJvUGNSRlMFHj95GZb-1PrAFYWiAG0a-hGbUbMDqd6Cg9ShV2qCVFzOizdVY0'),
        'private_key' => env('VAPID_PRIVATE_KEY', 'Wh0qROtR6hyODRRWKuOu13MKUGnnHyULY5IIiNyg-e0'),
    ],
];
