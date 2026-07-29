<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FormShell from '@/Components/Organisms/FormShell.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'

defineOptions({ layout: AdminLayout })

interface Setting {
    key: string
    value: string | null
    type: string
    is_secret?: boolean
}

interface Props {
    // Keyed by DB `group`; we flatten to a per-key lookup below because the
    // tab layout (order, labels, input types) is defined here, not by `group`.
    settings: Record<string, Setting[]>
}

const props = defineProps<Props>()

// A media row as emitted by AppMediaPicker. Settings store only the URL string,
// so we adapt in both directions (mediaModel / setMedia).
interface MediaItem {
    id: number | string
    url?: string | null
}

type FieldInput = 'text' | 'textarea' | 'image' | 'email' | 'tel' | 'url'

interface FieldDef {
    key: string
    label: string
    input?: FieldInput
    placeholder?: string
    help?: string
}

interface TabDef {
    key: string
    label: string
    fields: FieldDef[]
}

const TABS: TabDef[] = [
    {
        key: 'general',
        label: 'General',
        fields: [
            { key: 'site_name', label: 'Site Name', placeholder: 'My Website' },
            {
                key: 'site_description',
                label: 'Site Tagline',
                input: 'textarea',
                help: 'A short slogan. Also used as the default SEO meta description when a page has none.',
            },
            { key: 'site_logo', label: 'Logo', input: 'image' },
            {
                key: 'site_favicon',
                label: 'Favicon',
                input: 'image',
                help: 'Square PNG recommended (512×512).',
            },
        ],
    },
    {
        key: 'contact',
        label: 'Contact Information',
        fields: [
            { key: 'contact_phone', label: 'Phone Number', input: 'tel', placeholder: '+1 234 567 890' },
            { key: 'contact_email', label: 'Email', input: 'email', placeholder: 'info@example.com' },
            { key: 'address', label: 'Address', input: 'textarea' },
        ],
    },
    {
        key: 'social',
        label: 'Social Media',
        fields: [
            { key: 'facebook', label: 'Facebook', input: 'url', placeholder: 'https://facebook.com/…' },
            { key: 'twitter', label: 'X / Twitter', input: 'url', placeholder: 'https://x.com/…' },
            { key: 'instagram', label: 'Instagram', input: 'url', placeholder: 'https://instagram.com/…' },
            { key: 'linkedin', label: 'LinkedIn', input: 'url', placeholder: 'https://linkedin.com/…' },
            { key: 'youtube', label: 'YouTube', input: 'url', placeholder: 'https://youtube.com/@…' },
            { key: 'whatsapp', label: 'WhatsApp', input: 'tel', placeholder: '+1 234 567 890' },
        ],
    },
    {
        key: 'shop',
        label: 'Shop Settings',
        fields: [
            { key: 'shop_location', label: 'Shop Location', placeholder: 'City, Country' },
            { key: 'shop_currency', label: 'Currency Code', placeholder: 'USD', help: 'ISO 4217 code, e.g. USD, EUR, BDT.' },
            { key: 'shop_currency_symbol', label: 'Currency Symbol', placeholder: '$' },
        ],
    },
    {
        key: 'seo',
        label: 'SEO & Analytics',
        fields: [
            { key: 'og_image', label: 'Social Share Image', input: 'image', help: 'Shown when the site is shared (og:image / Twitter card).' },
            { key: 'ga_measurement_id', label: 'Google Analytics Measurement ID', placeholder: 'G-XXXXXXXXXX' },
            { key: 'gtm_container_id', label: 'Google Tag Manager Container ID', placeholder: 'GTM-XXXXXXX' },
            { key: 'cookie_consent_text', label: 'Cookie Consent Banner Text', input: 'textarea' },
        ],
    },
]

// Flatten the grouped payload into a per-key lookup. Secret values arrive from
// the server blanked (Admin\SettingController::index); keep them blank so an
// unmodified submit is treated as "leave the existing value alone" server-side.
const byKey: Record<string, Setting> = {}
Object.values(props.settings).forEach((group) => {
    group.forEach((s) => { byKey[s.key] = s })
})

// Seed the form from every existing row so keys not shown in any tab still
// round-trip unchanged, then guarantee every defined field key is present.
const settingsObj: Record<string, string> = {}
Object.values(props.settings).forEach((group) => {
    group.forEach((s) => { settingsObj[s.key] = s.value ?? '' })
})
TABS.forEach((tab) => tab.fields.forEach((f) => {
    if (!(f.key in settingsObj)) settingsObj[f.key] = ''
}))

const form = useForm({ settings: settingsObj })

const activeTab = ref<string>(TABS[0]!.key)

function isSecret(key: string): boolean {
    return byKey[key]?.is_secret === true
}

function resolveInput(field: FieldDef): FieldInput | 'password' {
    // A secret flag (SMTP/gateway keys added by a project) always wins over the
    // declared input type so a real secret is never rendered in cleartext.
    if (isSecret(field.key)) return 'password'
    return field.input ?? 'text'
}

function fieldHelp(field: FieldDef): string | undefined {
    if (isSecret(field.key)) return 'Stored encrypted. Leave blank to keep the current value.'
    return field.help
}

// AppMediaPicker binds a media object; settings persist the URL string only.
function mediaModel(key: string): MediaItem | null {
    const value = form.settings[key]
    return value ? { id: key, url: value } : null
}

function setMedia(key: string, media: MediaItem | null): void {
    form.settings[key] = media?.url ?? ''
}

const activeFields = computed<FieldDef[]>(
    () => TABS.find((t) => t.key === activeTab.value)?.fields ?? [],
)
</script>

<template>
    <Head title="Settings" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Site Settings</h1>

    <!-- Tab bar -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto" role="tablist" aria-label="Settings sections">
            <button
                v-for="tab in TABS"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.key"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                :class="activeTab === tab.key
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                @click="activeTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </nav>
    </div>

    <!-- One form spanning every tab: switching tabs only swaps which inputs are
         mounted; form.settings still holds all keys, so Save persists them all. -->
    <FormShell :form="form" action="/admin/settings" method="put" submit-label="Save Settings">
        <AppFormSection>
            <template v-for="field in activeFields" :key="field.key">
                <AppFormField
                    :name="`settings.${field.key}`"
                    :label="field.label"
                    :help="fieldHelp(field)"
                >
                    <template #default="{ id, invalid }">
                        <AppMediaPicker
                            v-if="resolveInput(field) === 'image'"
                            :model-value="mediaModel(field.key)"
                            :label="`Choose ${field.label.toLowerCase()}`"
                            @update:model-value="(m) => setMedia(field.key, m)"
                        />
                        <AppTextarea
                            v-else-if="resolveInput(field) === 'textarea'"
                            :id="id"
                            v-model="form.settings[field.key]"
                            :rows="3"
                            :placeholder="field.placeholder"
                            :invalid="invalid"
                        />
                        <AppInput
                            v-else
                            :id="id"
                            v-model="form.settings[field.key]"
                            :type="resolveInput(field) === 'password' ? 'password' : 'text'"
                            :inputmode="resolveInput(field) === 'tel' ? 'tel'
                                : resolveInput(field) === 'email' ? 'email'
                                : resolveInput(field) === 'url' ? 'url'
                                : undefined"
                            :autocomplete="resolveInput(field) === 'password' ? 'new-password' : 'off'"
                            :placeholder="resolveInput(field) === 'password'
                                ? '•••••••• (leave blank to keep current)'
                                : field.placeholder"
                            :invalid="invalid"
                        />
                    </template>
                </AppFormField>
            </template>
        </AppFormSection>
    </FormShell>
</template>
