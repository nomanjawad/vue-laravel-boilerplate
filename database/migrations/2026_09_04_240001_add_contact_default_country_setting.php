<?php

use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;

/**
 * Default country ISO code for contact-form phone validation
 * (propaganistas/laravel-phone). Whitelist-by-existence requires a row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Setting::firstOrCreate(
            ['key' => 'contact_default_country'],
            ['value' => 'BD', 'type' => 'string', 'group' => 'contact'],
        );
    }

    public function down(): void
    {
        Setting::where('key', 'contact_default_country')->delete();
    }
};
