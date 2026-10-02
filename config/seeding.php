<?php

return [
    'enabled' => (bool) env('EVENTS_SEED_USER_ENABLED', false),
    'name' => env('EVENTS_SEED_USER_NAME'),
    'email' => env('EVENTS_SEED_USER_EMAIL'),
    'password' => env('EVENTS_SEED_USER_PASSWORD'),
    'filament_access' => (bool) env('EVENTS_SEED_FILAMENT_ACCESS', false),
];
