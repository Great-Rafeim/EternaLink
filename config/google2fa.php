<?php

return [
    'enabled' => env('OTP_ENABLED', true),
    'lifetime' => env('OTP_LIFETIME', 0),
    'keep_alive' => env('OTP_KEEP_ALIVE', true),
    'auth' => 'auth',
    'guard' => '',
    'session_var' => 'google2fa',
    'otp_input' => 'one_time_password',
    'window' => 1,
    'forbid_old_passwords' => false,
    'otp_secret_column' => 'google2fa_secret',
    'view' => 'auth.2fa_verify',
    'session_variable' => 'google2fa_passed',
    'error_messages' => [
        'wrong_otp' => "The 'One Time Password' typed was wrong.",
        'cannot_be_empty' => 'One Time Password cannot be empty.',
        'unknown' => 'An unknown error has occurred. Please try again.',
    ],
    'throw_exceptions' => env('OTP_THROW_EXCEPTION', true),
    'qrcode_image_backend' => \PragmaRX\Google2FALaravel\Support\Constants::QRCODE_IMAGE_BACKEND_SVG,
];