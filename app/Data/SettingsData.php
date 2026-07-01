<?php

namespace App\Data;

use Spatie\LaravelData\Data;
use Spatie\LaravelData\Optional;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

/**
 * Public site settings whitelisted in HandleInertiaRequests::PUBLIC_SETTINGS.
 * Every key is optional because fresh installs may not have seeded values yet.
 */
#[TypeScript]
class SettingsData extends Data
{
    public function __construct(
        public string|Optional|null $site_name = null,
        public string|Optional|null $site_description = null,
        public string|Optional|null $og_image = null,
        public string|Optional|null $contact_email = null,
        public string|Optional|null $contact_phone = null,
        public string|Optional|null $address = null,
        public string|Optional|null $whatsapp = null,
        public string|Optional|null $facebook = null,
        public string|Optional|null $twitter = null,
        public string|Optional|null $instagram = null,
        public string|Optional|null $linkedin = null,
        public string|Optional|null $youtube = null,
        public string|Optional|null $shop_currency = null,
        public string|Optional|null $shop_currency_symbol = null,
        public string|Optional|null $ga_measurement_id = null,
        public string|Optional|null $gtm_container_id = null,
        public string|Optional|null $cookie_consent_text = null,
    ) {}
}
