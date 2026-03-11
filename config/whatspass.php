<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Phone Number ID
    |--------------------------------------------------------------------------
    |
    | The Phone Number ID from your Meta for Developers WhatsApp Business App.
    | Found at: https://developers.facebook.com/apps/{app_id}/whatsapp-business/
    |
    */
    'phone_number_id' => env('WHATSPASS_PHONE_NUMBER_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Meta WhatsApp Access Token
    |--------------------------------------------------------------------------
    |
    | The permanent or temporary access token for the WhatsApp Business API.
    | Generate one from the Meta for Developers dashboard.
    |
    */
    'access_token' => env('WHATSPASS_ACCESS_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | Meta Graph API Version
    |--------------------------------------------------------------------------
    |
    | The version of the Meta Graph API to use.
    |
    */
    'api_version' => env('WHATSPASS_API_VERSION', 'v19.0'),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Meta Graph API. Override only for testing.
    |
    */
    'base_url' => env('WHATSPASS_BASE_URL', 'https://graph.facebook.com'),

    /*
    |--------------------------------------------------------------------------
    | Default OTP Template Name
    |--------------------------------------------------------------------------
    |
    | The name of your pre-approved WhatsApp Message Template for OTP delivery.
    | This template must be approved in the Meta Business Manager.
    |
    */
    'default_template_name' => env('WHATSPASS_TEMPLATE_NAME', 'otp_authentication'),

    /*
    |--------------------------------------------------------------------------
    | Default Language Code
    |--------------------------------------------------------------------------
    |
    | The BCP-47 language code for the OTP template (e.g., en_US, es_ES, pt_BR).
    |
    */
    'default_language_code' => env('WHATSPASS_LANGUAGE_CODE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | OTP Length
    |--------------------------------------------------------------------------
    |
    | The number of characters in the generated OTP. Must be between 4 and 12.
    |
    */
    'otp_length' => (int) env('WHATSPASS_OTP_LENGTH', 6),

    /*
    |--------------------------------------------------------------------------
    | OTP Expiry (seconds)
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) an OTP should remain valid. Minimum 60 seconds.
    | Note: This library does not store OTPs — handle expiry in your app.
    |
    */
    'otp_expiry' => (int) env('WHATSPASS_OTP_EXPIRY', 300),

    /*
    |--------------------------------------------------------------------------
    | Alphanumeric OTP
    |--------------------------------------------------------------------------
    |
    | Set to true to generate alphanumeric OTPs instead of numeric-only.
    |
    */
    'alphanumeric_otp' => (bool) env('WHATSPASS_ALPHANUMERIC_OTP', false),
];
