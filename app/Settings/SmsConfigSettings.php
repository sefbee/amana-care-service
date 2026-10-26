<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class SmsConfigSettings extends Settings
{
    public string $otp_send = '';

    public string $order_confirmation = '';

    public string $request_product = '';

    public static function group(): string
    {
        return 'sms_config_setup';
    }
}
