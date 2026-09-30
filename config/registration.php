<?php
return [
 'enabled'=>(bool)env('REGISTRATION_PAYMENTS_ENABLED',false),
 'live'=>(bool)env('REGISTRATION_STRIPE_LIVE',false),
 'stripe_secret'=>env('REGISTRATION_STRIPE_SECRET'),
 'webhook_secret'=>env('REGISTRATION_STRIPE_WEBHOOK_SECRET'),
 // Pin the tested Stripe API version; do not inherit dashboard upgrades implicitly.
 'stripe_api_version'=>'2025-06-30.basil',
];
