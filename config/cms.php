<?php

return [
    'media_disk' => env('CMS_MEDIA_DISK', 'public'),
    'turnstile_secret' => env('CMS_TURNSTILE_SECRET'),
    'turnstile_site_key' => env('CMS_TURNSTILE_SITE_KEY'),
    'turnstile_verify_url' => env('CMS_TURNSTILE_VERIFY_URL', 'https://challenges.cloudflare.com/turnstile/v0/siteverify'),
];
