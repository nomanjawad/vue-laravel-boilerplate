<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import FormShell from '@/Components/Organisms/FormShell.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'

defineOptions({ layout: AdminLayout })

type FieldInput = 'text' | 'textarea' | 'image' | 'email' | 'tel' | 'url' | 'toggle' | 'password' | 'color' | 'select'

interface SelectOption {
    value: string
    label: string
}

interface SettingField {
    key: string
    label: string
    input: FieldInput | string
    placeholder?: string | null
    help?: string | null
    is_secret: boolean
    value?: string | null
    options?: SelectOption[] | null
}

interface SettingGroup {
    group: string
    label: string
    fields: SettingField[]
}

interface Props {
    groups: SettingGroup[]
    seoIndexable: boolean
}

const props = defineProps<Props>()

interface MediaItem {
    id: number | string
    url?: string | null
}

// Seed form from every field so Save round-trips all groups; switching tabs
// only swaps which inputs are mounted.
const settingsObj: Record<string, string> = {}
props.groups.forEach((g) => {
    g.fields.forEach((f) => {
        settingsObj[f.key] = f.value ?? ''
    })
})

const form = useForm({ settings: settingsObj })

const activeTab = ref<string>(props.groups[0]?.group ?? 'general')

const activeGroup = computed(
    () => props.groups.find((g) => g.group === activeTab.value) ?? null,
)

function resolveInput(field: SettingField): FieldInput | 'password' {
    if (field.is_secret) return 'password'
    return (field.input as FieldInput) || 'text'
}

function fieldHelp(field: SettingField): string | undefined {
    if (field.is_secret) return 'Stored encrypted. Leave blank to keep the current value.'
    return field.help ?? undefined
}

function mediaModel(key: string): MediaItem | null {
    const value = form.settings[key]
    return value ? { id: key, url: value } : null
}

function setMedia(key: string, media: MediaItem | null): void {
    form.settings[key] = media?.url ?? ''
}

/** Normalize hex for <input type="color"> (needs #rrggbb). */
function colorValue(raw: string | undefined): string {
    const v = (raw ?? '').trim()
    if (/^#[0-9a-fA-F]{6}$/.test(v)) return v.toLowerCase()
    if (/^#[0-9a-fA-F]{3}$/.test(v)) {
        return `#${v[1]}${v[1]}${v[2]}${v[2]}${v[3]}${v[3]}`.toLowerCase()
    }
    return '#6366f1'
}

function fontPreviewFamily(field: SettingField): string {
    const key = form.settings[field.key] ?? ''
    const match = field.options?.find((o) => o.value === key)
    return match ? `'${match.label}', ui-sans-serif, system-ui, sans-serif` : 'inherit'
}

// Preview non-default bunny fonts in the Theme tab (Instrument Sans ships via Vite).
watch(
    () => form.settings.theme_font,
    (slug) => {
        if (!slug || slug === 'instrument-sans' || typeof document === 'undefined') return
        const id = 'theme-font-preview'
        let link = document.getElementById(id) as HTMLLinkElement | null
        if (!link) {
            link = document.createElement('link')
            link.id = id
            link.rel = 'stylesheet'
            document.head.appendChild(link)
        }
        link.href = `https://fonts.bunny.net/css?family=${encodeURIComponent(slug)}:400,500,600&display=swap`
    },
    { immediate: true },
)
</script>

<template>
    <Head title="Settings" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Site Settings</h1>

    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto" role="tablist" aria-label="Settings sections">
            <button
                v-for="tab in groups"
                :key="tab.group"
                type="button"
                role="tab"
                :aria-selected="activeTab === tab.group"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                :class="activeTab === tab.group
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                @click="activeTab = tab.group"
            >
                {{ tab.label }}
            </button>
        </nav>
    </div>

    <FormShell :form="form" action="/admin/settings" method="put" submit-label="Save Settings">
        <!-- SEO indexability gate (mirrors TemplateDoctor::checkIndexability) -->
        <div
            v-if="activeTab === 'seo' && !seoIndexable"
            class="mb-6 rounded-lg border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm text-amber-200"
            role="alert"
        >
            <p class="font-semibold text-amber-100">Site is invisible to search engines</p>
            <p class="mt-1 text-amber-200/90">
                <code class="rounded bg-black/20 px-1 py-0.5 text-xs">SEO_INDEXABLE</code>
                is not <code class="rounded bg-black/20 px-1 py-0.5 text-xs">true</code>
                — every crawler gets noindex/nofollow (meta, header, and robots.txt).
                Set <code class="rounded bg-black/20 px-1 py-0.5 text-xs">SEO_INDEXABLE=true</code>
                in <code class="rounded bg-black/20 px-1 py-0.5 text-xs">.env</code> and run
                <code class="rounded bg-black/20 px-1 py-0.5 text-xs">php artisan config:cache</code>
                to go live.
            </p>
        </div>
        <div
            v-else-if="activeTab === 'seo' && seoIndexable"
            class="mb-6 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
        >
            <p class="font-medium">
                <code class="rounded bg-black/20 px-1 py-0.5 text-xs">SEO_INDEXABLE=true</code>
                — site is crawlable.
            </p>
        </div>

        <AppFormSection v-if="activeGroup?.fields.length">
            <template v-for="field in activeGroup!.fields" :key="field.key">
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
                            :placeholder="field.placeholder ?? undefined"
                            :invalid="invalid"
                        />
                        <AppSwitch
                            v-else-if="resolveInput(field) === 'toggle'"
                            :model-value="form.settings[field.key] === '1'"
                            @update:model-value="(v) => { form.settings[field.key] = v ? '1' : '' }"
                        />
                        <div
                            v-else-if="resolveInput(field) === 'color'"
                            class="flex items-center gap-3"
                        >
                            <input
                                :id="id"
                                type="color"
                                class="h-10 w-14 cursor-pointer rounded border border-gray-300 bg-white p-1"
                                :value="colorValue(form.settings[field.key])"
                                :aria-invalid="invalid"
                                @input="(e) => { form.settings[field.key] = (e.target as HTMLInputElement).value }"
                            >
                            <div class="min-w-0 flex-1">
                                <AppInput
                                    v-model="form.settings[field.key]"
                                    type="text"
                                    :placeholder="field.placeholder ?? '#6366f1'"
                                    :invalid="invalid"
                                    autocomplete="off"
                                />
                            </div>
                        </div>
                        <div v-else-if="resolveInput(field) === 'select'" class="space-y-2">
                            <AppSelect
                                :id="id"
                                :model-value="form.settings[field.key]"
                                :options="field.options ?? []"
                                :placeholder="field.placeholder ?? 'Select…'"
                                :invalid="invalid"
                                @update:model-value="(v) => { form.settings[field.key] = v }"
                            />
                            <p
                                v-if="field.key === 'theme_font'"
                                class="rounded border border-gray-200 bg-gray-50 px-3 py-2 text-sm text-gray-700"
                                :style="{ fontFamily: fontPreviewFamily(field) }"
                            >
                                The quick brown fox jumps over the lazy dog.
                            </p>
                        </div>
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
                                : (field.placeholder ?? undefined)"
                            :invalid="invalid"
                        />
                    </template>
                </AppFormField>
            </template>
        </AppFormSection>
    </FormShell>
</template>
