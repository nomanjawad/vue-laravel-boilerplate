<script setup lang="ts">
import { computed, ref } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'
import AppBlockEditor from '@/Components/Organisms/AppBlockEditor.vue'
import AppFloatingSave from '@/Components/Molecules/AppFloatingSave.vue'
import SeoSerpPreview from '@/Components/Molecules/SeoSerpPreview.vue'
import type { VariantEntry } from '@/Composables/useImageUrl'
import { useClickOutside } from '@/Composables/useClickOutside'
import { useUnsavedGuard } from '@/Composables/useUnsavedGuard'

defineOptions({ layout: AdminLayout })

interface WidgetField {
    key: string
    label: string
    type: string
    default?: unknown
    placeholder?: string
    options?: { value: string; label: string }[]
    item_fields?: WidgetField[]
    source?: string
    mode?: string
    limit?: number
}

interface WidgetDef {
    key: string
    label: string
    icon?: string
    fields: WidgetField[]
}

interface WidgetInstance {
    id: string
    type: string
    visible: boolean
    data: Record<string, any>
}

interface SeoBlock {
    title: string
    description: string
    noindex: boolean
    json_ld: string
}

interface PagePayload {
    slug: string
    title: string
    status: string
    featured_image?: string
    seo: SeoBlock
    widgets: WidgetInstance[]
}

interface Props {
    page: PagePayload | null
    widgetsRegistry: WidgetDef[]
    pages?: { slug: string; title: string }[]
    isCreate: boolean
}

const props = withDefaults(defineProps<Props>(), {
    pages: () => [],
})

const registryByKey = computed(() => {
    const map: Record<string, WidgetDef> = {}
    for (const w of props.widgetsRegistry) {
        map[w.key] = w
    }
    return map
})

function defaultsFor(type: string): Record<string, any> {
    const def = registryByKey.value[type]
    const data: Record<string, any> = {}
    if (!def) return data
    for (const field of def.fields) {
        data[field.key] = field.default ?? (field.type === 'boolean' ? false : field.type === 'repeater' || field.type === 'collection' ? (field.default ?? []) : '')
        if (field.type === 'collection' && !data[field.key]) {
            data[field.key] = {
                mode: field.mode || 'latest',
                ids: [],
                limit: field.limit ?? 6,
            }
        }
    }
    return data
}

function newWidgetId(): string {
    return 'w_' + Math.random().toString(36).slice(2, 10)
}

const form = useForm<Record<string, any>>({
    title: props.page?.title ?? '',
    slug: props.page?.slug ?? '',
    status: props.page?.status ?? 'draft',
    featured_image: props.page?.featured_image ?? '',
    seo: {
        title: props.page?.seo?.title ?? '',
        description: props.page?.seo?.description ?? '',
        noindex: props.page?.seo?.noindex ?? false,
        json_ld: props.page?.seo?.json_ld ?? '',
    },
    widgets: (props.page?.widgets ?? []) as WidgetInstance[],
})

const expanded = ref<Record<string, boolean>>({})
const showPalette = ref(false)
const paletteFilter = ref('')
const paletteRoot = ref<HTMLElement | null>(null)

useClickOutside(paletteRoot, () => { showPalette.value = false }, { enabled: () => showPalette.value })
useUnsavedGuard(() => form.isDirty)

const formErrors = computed(() =>
    Object.entries(form.errors)
        .filter(([, msg]) => typeof msg === 'string' && msg.length > 0)
        .map(([field, msg]) => ({ field, msg: msg as string })),
)

function isExpanded(id: string): boolean {
    return expanded.value[id] !== false
}

function toggleExpanded(id: string) {
    expanded.value[id] = !isExpanded(id)
}

function isCollectionWidget(def: WidgetDef): boolean {
    return (def.fields ?? []).some((f) => f.type === 'collection')
}

const filteredRegistry = computed(() => {
    const q = paletteFilter.value.trim().toLowerCase()
    return props.widgetsRegistry.filter((d) => !q || d.label.toLowerCase().includes(q) || d.key.includes(q))
})

const staticWidgets = computed(() => filteredRegistry.value.filter((d) => !isCollectionWidget(d)))
const dynamicWidgets = computed(() => filteredRegistry.value.filter((d) => isCollectionWidget(d)))

function addWidget(type: string) {
    const widget: WidgetInstance = {
        id: newWidgetId(),
        type,
        visible: true,
        data: defaultsFor(type),
    }
    form.widgets = [...form.widgets, widget]
    expanded.value[widget.id] = true
    showPalette.value = false
    paletteFilter.value = ''
}

function removeWidget(index: number | string) {
    const i = Number(index)
    const next = [...form.widgets]
    next.splice(i, 1)
    form.widgets = next
}

function moveWidget(index: number | string, dir: -1 | 1) {
    const i = Number(index)
    const target = i + dir
    if (target < 0 || target >= form.widgets.length) return
    const next = [...form.widgets]
    const tmp = next[i]
    next[i] = next[target]
    next[target] = tmp
    form.widgets = next
}

function setWidgetField(index: number | string, key: string, value: unknown) {
    const i = Number(index)
    const widgets = [...form.widgets]
    widgets[i] = {
        ...widgets[i],
        data: { ...widgets[i].data, [key]: value },
    }
    form.widgets = widgets
}

function setRepeaterItem(widgetIndex: number | string, fieldKey: string, itemIndex: number | string, itemKey: string, value: unknown) {
    const wi = Number(widgetIndex)
    const ii = Number(itemIndex)
    const widgets = [...form.widgets]
    const items = [...(widgets[wi].data[fieldKey] || [])]
    items[ii] = { ...items[ii], [itemKey]: value }
    widgets[wi] = {
        ...widgets[wi],
        data: { ...widgets[wi].data, [fieldKey]: items },
    }
    form.widgets = widgets
}

function addRepeaterItem(widgetIndex: number | string, field: WidgetField) {
    const wi = Number(widgetIndex)
    const item: Record<string, any> = {}
    for (const f of field.item_fields || []) {
        item[f.key] = f.default ?? ''
    }
    const widgets = [...form.widgets]
    const items = [...(widgets[wi].data[field.key] || []), item]
    widgets[wi] = {
        ...widgets[wi],
        data: { ...widgets[wi].data, [field.key]: items },
    }
    form.widgets = widgets
}

function removeRepeaterItem(widgetIndex: number | string, fieldKey: string, itemIndex: number | string) {
    const wi = Number(widgetIndex)
    const ii = Number(itemIndex)
    const widgets = [...form.widgets]
    const items = [...(widgets[wi].data[fieldKey] || [])]
    items.splice(ii, 1)
    widgets[wi] = {
        ...widgets[wi],
        data: { ...widgets[wi].data, [fieldKey]: items },
    }
    form.widgets = widgets
}

function mediaModel(value: unknown): { id: string | number; url: string; variants?: Record<string, VariantEntry> | null; width?: number | null; height?: number | null; alt_text?: string | null } | null {
    if (!value) return null
    if (typeof value === 'object' && value !== null && 'url' in value) {
        const m = value as { id?: string | number; url?: string; variants?: Record<string, VariantEntry> | null; width?: number | null; height?: number | null; alt_text?: string | null }
        return m.url ? {
            id: m.id ?? m.url,
            url: m.url,
            variants: m.variants ?? null,
            width: m.width ?? null,
            height: m.height ?? null,
            alt_text: m.alt_text ?? null,
        } : null
    }
    if (typeof value === 'string') return { id: value, url: value }
    return null
}

/** Persist enough media metadata for AppImage srcset/dims on the public site. */
function mediaValue(m: unknown): Record<string, unknown> | string {
    if (!m || typeof m !== 'object') return ''
    const media = m as { id?: string | number; url?: string; variants?: unknown; width?: number | null; height?: number | null; alt_text?: string | null }
    if (!media.url) return ''
    return {
        id: media.id ?? media.url,
        url: media.url,
        variants: media.variants ?? null,
        width: media.width ?? null,
        height: media.height ?? null,
        alt_text: media.alt_text ?? null,
    }
}

/** OG/canonical consumers need a plain URL string. */
function mediaUrlOnly(m: unknown): string {
    if (!m || typeof m !== 'object') return ''
    const media = m as { url?: string }
    return media.url || ''
}

function save() {
    if (props.isCreate) {
        form.post('/admin/pages')
    } else {
        form.put(`/admin/pages/${props.page!.slug}`, { preserveScroll: true })
    }
}

// Slugify title on create when slug empty / still tracking the previous title.
function onTitleInput(v: string) {
    const previous = form.title
    form.title = v
    if (props.isCreate && (!form.slug || form.slug === slugify(previous))) {
        form.slug = slugify(v)
    }
}

function slugify(s: string): string {
    return s
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
}
</script>

<template>
    <Head :title="isCreate ? 'New Page' : `Edit: ${form.title}`" />

    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">{{ isCreate ? 'New Page' : 'Edit Page' }}</h1>
    </div>

    <div
        v-if="formErrors.length"
        class="mb-4 rounded-lg border border-rose-300 bg-rose-50 px-4 py-3 text-sm text-rose-800"
        role="alert"
    >
        <p class="font-medium">Couldn’t save — fix the following:</p>
        <ul class="mt-1 list-inside list-disc space-y-0.5">
            <li v-for="err in formErrors" :key="err.field">
                <span class="font-mono text-xs text-rose-600">{{ err.field }}</span> — {{ err.msg }}
            </li>
        </ul>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Widgets column -->
        <div class="space-y-4 lg:col-span-2">
            <div
                v-for="(widget, wi) in form.widgets"
                :key="widget.id"
                class="rounded-lg border border-gray-200 bg-white shadow-sm"
            >
                <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
                    <button type="button" class="text-sm font-medium text-gray-900" @click="toggleExpanded(widget.id)">
                        {{ registryByKey[widget.type]?.label || widget.type }}
                    </button>
                    <span class="text-xs text-gray-400">{{ widget.id }}</span>
                    <div class="ml-auto flex items-center gap-2">
                        <label class="flex items-center gap-1 text-xs text-gray-500">
                            <input v-model="widget.visible" type="checkbox" class="rounded border-gray-300" />
                            Visible
                        </label>
                        <button type="button" class="text-xs text-gray-500 hover:text-gray-800" :disabled="wi === 0" @click="moveWidget(wi, -1)">↑</button>
                        <button type="button" class="text-xs text-gray-500 hover:text-gray-800" :disabled="wi === form.widgets.length - 1" @click="moveWidget(wi, 1)">↓</button>
                        <button type="button" class="text-xs text-red-600 hover:text-red-800" @click="removeWidget(wi)">Remove</button>
                    </div>
                </div>

                <div v-show="isExpanded(widget.id)" class="space-y-4 p-4">
                    <template v-for="field in (registryByKey[widget.type]?.fields || [])" :key="field.key">
                        <!-- text / link / number -->
                        <AppFormField
                            v-if="['text', 'link', 'number'].includes(field.type)"
                            :name="`${widget.id}-${field.key}`"
                            :label="field.label"
                        >
                            <template #default="{ id }">
                                <AppInput
                                    :id="id"
                                    :type="field.type === 'number' ? 'number' : 'text'"
                                    :model-value="widget.data[field.key] ?? ''"
                                    :placeholder="field.placeholder || ''"
                                    @update:model-value="(v) => setWidgetField(wi, field.key, v)"
                                />
                            </template>
                        </AppFormField>

                        <!-- richtext -->
                        <AppFormField
                            v-else-if="field.type === 'richtext'"
                            :name="`${widget.id}-${field.key}`"
                            :label="field.label"
                        >
                            <AppBlockEditor
                                compact
                                :model-value="String(widget.data[field.key] ?? '')"
                                :placeholder="field.placeholder || ''"
                                @update:model-value="(v) => setWidgetField(wi, field.key, v)"
                            />
                        </AppFormField>

                        <!-- textarea -->
                        <AppFormField
                            v-else-if="field.type === 'textarea'"
                            :name="`${widget.id}-${field.key}`"
                            :label="field.label"
                        >
                            <template #default="{ id }">
                                <AppTextarea
                                    :id="id"
                                    :rows="3"
                                    :model-value="widget.data[field.key] ?? ''"
                                    :placeholder="field.placeholder || ''"
                                    @update:model-value="(v) => setWidgetField(wi, field.key, v)"
                                />
                            </template>
                        </AppFormField>

                        <!-- boolean -->
                        <AppFormField
                            v-else-if="field.type === 'boolean'"
                            :name="`${widget.id}-${field.key}`"
                            :label="field.label"
                        >
                            <AppSwitch
                                :model-value="Boolean(widget.data[field.key])"
                                @update:model-value="(v) => setWidgetField(wi, field.key, v)"
                            />
                        </AppFormField>

                        <!-- image -->
                        <AppFormField
                            v-else-if="field.type === 'image'"
                            :name="`${widget.id}-${field.key}`"
                            :label="field.label"
                        >
                            <AppMediaPicker
                                :model-value="mediaModel(widget.data[field.key] || '')"
                                @update:model-value="(m) => setWidgetField(wi, field.key, mediaValue(m))"
                            />
                        </AppFormField>

                        <!-- collection (mode + limit for now) -->
                        <div v-else-if="field.type === 'collection'" class="space-y-2 rounded border border-dashed border-gray-200 p-3">
                            <p class="text-sm font-medium text-gray-700">{{ field.label }} <span class="text-xs text-gray-400">({{ field.source }})</span></p>
                            <AppFormField :name="`${widget.id}-${field.key}-mode`" label="Mode">
                                <AppSelect
                                    :model-value="widget.data[field.key]?.mode || field.mode || 'latest'"
                                    :options="widget.type === 'faqs'
                                        ? [
                                            { value: 'current_page', label: 'Current page' },
                                            { value: 'picked', label: 'Picked page' },
                                            { value: 'global', label: 'Global FAQs' },
                                        ]
                                        : [
                                            { value: 'latest', label: 'Latest' },
                                            { value: 'picked', label: 'Picked IDs' },
                                        ]"
                                    @update:model-value="(v) => setWidgetField(wi, field.key, { ...(widget.data[field.key] || {}), mode: v })"
                                />
                            </AppFormField>
                            <AppFormField
                                v-if="widget.type === 'faqs' && (widget.data[field.key]?.mode || field.mode) === 'picked'"
                                :name="`${widget.id}-${field.key}-page`"
                                label="Page"
                            >
                                <AppSelect
                                    :model-value="widget.data[field.key]?.page_slug || ''"
                                    :options="[
                                        { value: '', label: 'Select a page…' },
                                        ...pages.map((p) => ({ value: p.slug, label: `${p.title} (${p.slug})` })),
                                    ]"
                                    @update:model-value="(v) => setWidgetField(wi, field.key, { ...(widget.data[field.key] || {}), page_slug: v })"
                                />
                            </AppFormField>
                            <AppFormField :name="`${widget.id}-${field.key}-limit`" label="Limit">
                                <AppInput
                                    type="number"
                                    :model-value="widget.data[field.key]?.limit ?? field.limit ?? 6"
                                    placeholder="6"
                                    @update:model-value="(v) => setWidgetField(wi, field.key, { ...(widget.data[field.key] || {}), limit: Number(v) || 6 })"
                                />
                            </AppFormField>
                        </div>

                        <!-- repeater -->
                        <div v-else-if="field.type === 'repeater'" class="space-y-3">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-gray-700">{{ field.label }}</p>
                                <button type="button" class="text-xs text-indigo-600 hover:text-indigo-800" @click="addRepeaterItem(wi, field)">
                                    + Add
                                </button>
                            </div>
                            <div
                                v-for="(item, ii) in (widget.data[field.key] || [])"
                                :key="ii"
                                class="space-y-2 rounded border border-gray-100 bg-gray-50 p-3"
                            >
                                <div class="flex justify-end">
                                    <button type="button" class="text-xs text-red-600" @click="removeRepeaterItem(wi, field.key, ii)">Remove</button>
                                </div>
                                <AppFormField
                                    v-for="itemField in (field.item_fields || [])"
                                    :key="itemField.key"
                                    :name="`${widget.id}-${field.key}-${ii}-${itemField.key}`"
                                    :label="itemField.label"
                                >
                                    <template #default="{ id }">
                                        <AppMediaPicker
                                            v-if="itemField.type === 'image'"
                                            :model-value="mediaModel(item[itemField.key] || '')"
                                            @update:model-value="(m) => setRepeaterItem(wi, field.key, ii, itemField.key, mediaValue(m))"
                                        />
                                        <AppTextarea
                                            v-else-if="itemField.type === 'textarea'"
                                            :id="id"
                                            :rows="2"
                                            :model-value="item[itemField.key] ?? ''"
                                            :placeholder="itemField.placeholder || ''"
                                            @update:model-value="(v) => setRepeaterItem(wi, field.key, ii, itemField.key, v)"
                                        />
                                        <AppInput
                                            v-else
                                            :id="id"
                                            :model-value="item[itemField.key] ?? ''"
                                            :placeholder="itemField.placeholder || ''"
                                            @update:model-value="(v) => setRepeaterItem(wi, field.key, ii, itemField.key, v)"
                                        />
                                    </template>
                                </AppFormField>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div ref="paletteRoot" class="relative">
                <button
                    type="button"
                    class="w-full rounded-lg border-2 border-dashed border-gray-300 px-4 py-3 text-sm font-medium text-gray-600 hover:border-gray-400 hover:text-gray-800"
                    :aria-expanded="showPalette"
                    @click="showPalette = !showPalette"
                >
                    {{ showPalette ? 'Close widget palette' : '+ Add widget' }}
                </button>
                <div
                    v-if="showPalette"
                    class="absolute z-20 mt-2 w-full rounded-lg border border-gray-200 bg-white p-3 shadow-lg"
                    role="dialog"
                    aria-label="Add widget"
                >
                    <div class="mb-2 flex items-center gap-2">
                        <AppInput
                            v-model="paletteFilter"
                            placeholder="Filter widgets…"
                            class="flex-1"
                        />
                        <button
                            type="button"
                            class="rounded px-2 py-1 text-sm text-gray-500 hover:bg-gray-100 hover:text-gray-800"
                            aria-label="Close"
                            @click="showPalette = false"
                        >
                            ×
                        </button>
                    </div>

                    <div v-if="staticWidgets.length" class="mb-3">
                        <p class="mb-1 px-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Static</p>
                        <div class="grid grid-cols-2 gap-1 sm:grid-cols-3">
                            <button
                                v-for="def in staticWidgets"
                                :key="def.key"
                                type="button"
                                class="rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700"
                                @click="addWidget(def.key)"
                            >
                                {{ def.label }}
                            </button>
                        </div>
                    </div>

                    <div v-if="dynamicWidgets.length">
                        <p class="mb-1 px-1 text-[11px] font-semibold uppercase tracking-wider text-gray-400">Dynamic</p>
                        <div class="grid grid-cols-2 gap-1 sm:grid-cols-3">
                            <button
                                v-for="def in dynamicWidgets"
                                :key="def.key"
                                type="button"
                                class="rounded px-3 py-2 text-left text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700"
                                @click="addWidget(def.key)"
                            >
                                {{ def.label }}
                            </button>
                        </div>
                    </div>

                    <p v-if="!filteredRegistry.length" class="px-1 py-2 text-sm text-gray-500">No widgets match.</p>
                </div>
            </div>
        </div>

        <!-- Settings column -->
        <div class="space-y-6">
            <AppFormSection title="Page" description="Title, URL slug, and publish status.">
                <AppFormField name="title" label="Title">
                    <template #default="{ id }">
                        <AppInput :id="id" :model-value="form.title" placeholder="About Us" @update:model-value="onTitleInput" />
                    </template>
                </AppFormField>
                <AppFormField name="slug" label="Slug">
                    <template #default="{ id }">
                        <AppInput
                            :id="id"
                            :model-value="form.slug"
                            placeholder="about"
                            :disabled="!isCreate && page?.slug === 'home'"
                            @update:model-value="(v) => (form.slug = String(v).toLowerCase().replace(/[^a-z0-9-]/g, ''))"
                        />
                        <p v-if="form.errors.slug" class="mt-1 text-xs text-rose-600">{{ form.errors.slug }}</p>
                    </template>
                </AppFormField>
                <AppFormField name="status" label="Status">
                    <AppSelect
                        :model-value="form.status"
                        :options="[
                            { value: 'published', label: 'Published' },
                            { value: 'draft', label: 'Draft' },
                        ]"
                        @update:model-value="(v) => (form.status = v)"
                    />
                </AppFormField>
                <AppFormField name="featured_image" label="Featured Image" help="Used as the social share (og:image) image.">
                    <AppMediaPicker
                        :model-value="mediaModel(form.featured_image)"
                        @update:model-value="(m) => (form.featured_image = mediaUrlOnly(m))"
                    />
                </AppFormField>
            </AppFormSection>

            <AppFormSection title="SEO" description="Search preview for this page. Canonical and Open Graph tags are derived automatically.">
                <SeoSerpPreview
                    :title="form.seo.title || form.title"
                    :description="form.seo.description"
                    :url="`https://example.com/${form.slug === 'home' ? '' : (form.slug || 'page')}`"
                />
                <AppFormField name="seo-title" label="Meta Title" help="Leave blank to use the site title template.">
                    <template #default="{ id }">
                        <AppInput :id="id" v-model="form.seo.title" placeholder="Falls back to title template" />
                    </template>
                </AppFormField>
                <AppFormField name="seo-description" label="Meta Description">
                    <template #default="{ id }">
                        <AppTextarea :id="id" v-model="form.seo.description" :rows="3" placeholder="Falls back to the site tagline" />
                    </template>
                </AppFormField>
                <AppFormField name="seo-noindex" label="Noindex">
                    <AppSwitch v-model="form.seo.noindex" />
                </AppFormField>
                <AppFormField name="seo-jsonld" label="Custom JSON-LD" help="Optional raw schema. Automatic BlogPosting / FAQPage / BreadcrumbList / LocalBusiness are generated separately.">
                    <template #default="{ id }">
                        <AppTextarea :id="id" v-model="form.seo.json_ld" :rows="4" placeholder='{"@context":"https://schema.org",...}' />
                        <p v-if="form.errors['seo.json_ld']" class="mt-1 text-xs text-rose-600">{{ form.errors['seo.json_ld'] }}</p>
                    </template>
                </AppFormField>
            </AppFormSection>
        </div>
    </div>

    <AppFloatingSave
        :dirty="form.isDirty"
        :processing="form.processing"
        @save="save"
    />
</template>
