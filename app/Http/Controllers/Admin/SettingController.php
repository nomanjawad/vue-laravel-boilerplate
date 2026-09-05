<?php

namespace App\Http\Controllers\Admin;

use App\Data\SettingFieldData;
use App\Data\SettingGroupData;
use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingService;
use App\Support\BrandPalette;
use App\Support\Theme;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class SettingController extends Controller
{
    /**
     * Canonical field metadata (label, input type, placeholder, help).
     * Single source for the Settings UI — DB rows supply values + group;
     * unknown keys still appear with a humanised label so a seeded group
     * without a map entry is never silently invisible.
     *
     * @var array<string, array{label: string, input?: string, placeholder?: string, help?: string, options?: list<array{value: string, label: string}>}>
     */
    private const FIELD_META = [
        'site_name' => [
            'label' => 'Site Name',
            'placeholder' => 'My Website',
        ],
        'site_description' => [
            'label' => 'Site Tagline',
            'input' => 'textarea',
            'placeholder' => 'A short slogan for your site',
            'help' => 'A short slogan. Also used as the default SEO meta description when a page has none.',
        ],
        'site_logo' => [
            'label' => 'Logo',
            'input' => 'image',
        ],
        'site_favicon' => [
            'label' => 'Favicon',
            'input' => 'image',
            'help' => 'Square PNG recommended (512×512).',
        ],
        'contact_phone' => [
            'label' => 'Phone Number',
            'input' => 'tel',
            'placeholder' => '+880 1XXX-XXXXXX',
        ],
        'contact_email' => [
            'label' => 'Email',
            'input' => 'email',
            'placeholder' => 'info@example.com',
        ],
        'contact_default_country' => [
            'label' => 'Default phone country',
            'placeholder' => 'BD',
            'help' => 'ISO 3166-1 alpha-2 code used to validate national phone numbers on the contact form (e.g. BD, US, GB). International +… numbers always work.',
        ],
        'address' => [
            'label' => 'Address',
            'input' => 'textarea',
            'placeholder' => '123 Main Street, City, Country',
        ],
        'facebook' => [
            'label' => 'Facebook',
            'input' => 'url',
            'placeholder' => 'https://facebook.com/…',
        ],
        'twitter' => [
            'label' => 'X / Twitter',
            'input' => 'url',
            'placeholder' => 'https://x.com/…',
        ],
        'instagram' => [
            'label' => 'Instagram',
            'input' => 'url',
            'placeholder' => 'https://instagram.com/…',
        ],
        'linkedin' => [
            'label' => 'LinkedIn',
            'input' => 'url',
            'placeholder' => 'https://linkedin.com/…',
        ],
        'youtube' => [
            'label' => 'YouTube',
            'input' => 'url',
            'placeholder' => 'https://youtube.com/@…',
        ],
        'whatsapp' => [
            'label' => 'WhatsApp',
            'input' => 'tel',
            'placeholder' => '+1 234 567 890',
        ],
        'og_image' => [
            'label' => 'Social Share Image',
            'input' => 'image',
            'help' => 'Shown when the site is shared (og:image / Twitter card).',
        ],
        'ga_measurement_id' => [
            'label' => 'Google Analytics Measurement ID',
            'placeholder' => 'G-XXXXXXXXXX',
        ],
        'gtm_container_id' => [
            'label' => 'Google Tag Manager Container ID',
            'placeholder' => 'GTM-XXXXXXX',
        ],
        'cookie_consent_text' => [
            'label' => 'Cookie Consent Banner Text',
            'input' => 'textarea',
            'placeholder' => 'We use cookies to improve your experience…',
        ],
        'site_noindex' => [
            'label' => 'Hide entire site from search engines',
            'input' => 'toggle',
            'help' => 'Adds a sitewide noindex meta tag to every public page. Useful while a site is in staging — turn off before launch.',
        ],
        'seo_title_template' => [
            'label' => 'Title template',
            'placeholder' => '%title% — %site_name%',
            'help' => 'Used when a page/post has no explicit meta title. Tokens: %title%, %site_name%.',
        ],
        'local_business_enabled' => [
            'label' => 'Emit LocalBusiness schema',
            'input' => 'toggle',
            'help' => 'Adds LocalBusiness JSON-LD site-wide (alongside Organization). Highest-value schema for service businesses.',
        ],
        'local_business_type' => [
            'label' => 'Business type',
            'placeholder' => 'LocalBusiness',
            'help' => 'schema.org type, e.g. LocalBusiness, ProfessionalService, MedicalBusiness, Dentist.',
        ],
        'local_business_street' => [
            'label' => 'Street address',
            'placeholder' => '123 Main Street',
        ],
        'local_business_city' => [
            'label' => 'City',
            'placeholder' => 'Dhaka',
        ],
        'local_business_region' => [
            'label' => 'Region / state',
            'placeholder' => 'Dhaka Division',
        ],
        'local_business_postal' => [
            'label' => 'Postal code',
            'placeholder' => '1205',
        ],
        'local_business_country' => [
            'label' => 'Country code',
            'placeholder' => 'BD',
            'help' => 'ISO 3166-1 alpha-2 (e.g. BD, US, GB).',
        ],
        'local_business_lat' => [
            'label' => 'Latitude',
            'placeholder' => '23.8103',
        ],
        'local_business_lng' => [
            'label' => 'Longitude',
            'placeholder' => '90.4125',
        ],
        'local_business_opening_hours' => [
            'label' => 'Opening hours',
            'input' => 'textarea',
            'placeholder' => "Mo-Fr 09:00-17:00\nSa 10:00-14:00",
            'help' => 'One entry per line (schema.org openingHours format). Phone falls back to Contact settings.',
        ],
        'theme_primary_color' => [
            'label' => 'Primary color',
            'input' => 'color',
            'placeholder' => BrandPalette::DEFAULT_PRIMARY,
            'help' => 'Brand accent for admin and public. A full 7-step palette is derived automatically; 300/500 lightness is clamped so links stay legible on the dark admin shell.',
        ],
        'theme_font' => [
            'label' => 'Font',
            'input' => 'select',
            'placeholder' => 'Choose a font',
            'help' => 'Loaded from fonts.bunny.net when not the default (Instrument Sans ships with the Vite build).',
            // Options injected at runtime from Theme::fontOptions() — const can't call methods.
        ],
        'theme_radius' => [
            'label' => 'Corner radius',
            'input' => 'select',
            'placeholder' => 'Medium',
            'help' => 'Applies to cards and buttons via --radius-card / --radius-button.',
        ],
    ];

    /** @var array<string, string> */
    private const GROUP_LABELS = [
        'general' => 'General',
        'contact' => 'Contact Information',
        'social' => 'Social Media',
        'seo' => 'SEO & Analytics',
        'theme' => 'Theme',
    ];

    /** Preferred tab order; unknown DB groups append after these. */
    private const GROUP_ORDER = ['general', 'contact', 'social', 'seo', 'theme'];

    public function __construct(private SettingService $settingService) {}

    public function index()
    {
        $rows = Setting::query()->orderBy('id')->get();

        // Redact secrets before shipping to Inertia (feedback.md §8).
        $byGroup = [];
        foreach ($rows as $setting) {
            $value = $setting->is_secret ? '' : ($setting->value ?? '');
            $byGroup[$setting->group][] = [
                'key' => $setting->key,
                'value' => $value,
                'type' => $setting->type,
                'is_secret' => (bool) $setting->is_secret,
            ];
        }

        $known = array_keys($byGroup);
        $ordered = array_values(array_unique(array_merge(
            array_values(array_filter(self::GROUP_ORDER, fn (string $g) => in_array($g, $known, true))),
            array_diff($known, self::GROUP_ORDER),
        )));

        $selectOptions = [
            'theme_font' => Theme::fontOptions(),
            'theme_radius' => Theme::radiusOptions(),
        ];

        $groups = [];
        foreach ($ordered as $groupKey) {
            $fields = [];
            foreach ($byGroup[$groupKey] as $row) {
                $meta = self::FIELD_META[$row['key']] ?? [
                    'label' => str_replace('_', ' ', ucfirst($row['key'])),
                ];
                $fields[] = SettingFieldData::from([
                    'key' => $row['key'],
                    'label' => $meta['label'],
                    'input' => $meta['input'] ?? 'text',
                    'placeholder' => $meta['placeholder'] ?? null,
                    'help' => $meta['help'] ?? null,
                    'is_secret' => $row['is_secret'],
                    'value' => $row['value'],
                    'options' => $selectOptions[$row['key']] ?? ($meta['options'] ?? null),
                ]);
            }

            $groups[] = SettingGroupData::from([
                'group' => $groupKey,
                'label' => self::GROUP_LABELS[$groupKey] ?? str_replace('_', ' ', ucfirst($groupKey)),
                'fields' => $fields,
            ]);
        }

        return Inertia::render('Admin/Settings/Index', [
            'groups' => $groups,
            'seoIndexable' => (bool) config('template.indexable'),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['nullable', 'string', 'max:5000'],
        ]);

        // Format-gate analytics ids so a crafted setting cannot XSS public pages (F11 #17).
        foreach ([
            'ga_measurement_id' => ['/^G-[A-Z0-9]+$/i', 'Google Analytics Measurement ID (e.g. G-XXXXXXXXXX)'],
            'gtm_container_id' => ['/^GTM-[A-Z0-9]+$/i', 'Google Tag Manager Container ID (e.g. GTM-XXXXXXX)'],
        ] as $key => [$pattern, $label]) {
            $val = trim((string) ($validated['settings'][$key] ?? ''));
            if ($val === '') {
                continue;
            }
            if (! preg_match($pattern, $val)) {
                throw ValidationException::withMessages([
                    "settings.{$key}" => "{$label} is invalid.",
                ]);
            }
        }

        // Blank submission on a secret field means "keep the existing value".
        $secretKeys = Setting::query()->where('is_secret', true)->pluck('key')->all();
        foreach ($secretKeys as $key) {
            if (array_key_exists($key, $validated['settings']) && ($validated['settings'][$key] ?? '') === '') {
                unset($validated['settings'][$key]);
            }
        }

        $this->settingService->update($validated['settings']);

        return back()->with('success', 'Settings updated successfully.');
    }
}
