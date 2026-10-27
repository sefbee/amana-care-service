<?php

return [
    // External notifications stay queued until the product owner approves these times.
    'quiet_hours' => [
        'start' => env('NOTIFICATIONS_QUIET_HOURS_START'),
        'end' => env('NOTIFICATIONS_QUIET_HOURS_END'),
        'timezone' => env('NOTIFICATIONS_QUIET_HOURS_TIMEZONE', env('APP_TIMEZONE', 'Asia/Dhaka')),
    ],
];
