<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'
import JsonContentEditor from '@/Components/Organisms/JsonContentEditor.vue'

defineOptions({ layout: AdminLayout })

type JsonValue = string | number | boolean | null | JsonValue[] | { [key: string]: JsonValue }
type JsonObject = Record<string, JsonValue>

interface ContentFile {
    file: string
    label: string
    data: JsonObject
}

interface Props {
    pages: ContentFile[]
}

const props = defineProps<Props>()

interface SeoBlock {
    title: string
    description: string
    og_image: string
    noindex: boolean
    json_ld: string
}

interface MediaItem {
    id: number | string
    url?: string | null
}

// One useForm per page, created once up front — switching pages only changes
// which form is *displayed*, so unsaved edits in a form that's currently
// hidden are never lost.
//
// The form's generic is kept as `Record<string, any>` rather than the
// recursive JsonObject type — feeding a recursive type into useForm's own
// (already-deep) conditional types blows up the TS compiler ("Type
// instantiation is excessively deep"). Helper functions below cast back to
// JsonObject at the point of use instead. Inertia's useForm() also reserves
// the key "data" (it's a method on the form instance), so the field is named
// "content" — the backend route reads `content` from the request body.
const forms: Record<string, ReturnType<typeof useForm<{ content: Record<string, any> }>>> = {}
props.pages.forEach((f) => {
    forms[f.file] = useForm<{ content: Record<string, any> }>({ content: f.data as Record<string, any> })
})

function contentOf(file: string): JsonObject {
    return (forms[file]?.content ?? {}) as JsonObject
}

const activeFile = ref<string>(props.pages[0]?.file ?? '')
const activeForm = computed(() => forms[activeFile.value] ?? null)

// The SEO block (title/description/og_image/noindex/json_ld) lives inside the
// page's JSON; splitting it out lets those fields use dedicated inputs while
// the rest of the page's JSON goes through the generic recursive editor.
function seoOf(file: string): SeoBlock {
    const seo = (contentOf(file).seo ?? {}) as Partial<SeoBlock>
    return {
        title: seo.title ?? '',
        description: seo.description ?? '',
        og_image: seo.og_image ?? '',
        noindex: seo.noindex ?? false,
        json_ld: seo.json_ld ?? '',
    }
}

function updateSeo(file: string, patch: Partial<SeoBlock>) {
    const form = forms[file]
    if (!form) return
    form.content = { ...contentOf(file), seo: { ...seoOf(file), ...patch } }
}

function restOf(file: string): JsonObject {
    const { seo: _seo, ...rest } = contentOf(file)
    return rest
}

function updateRest(file: string, rest: JsonObject) {
    const form = forms[file]
    if (!form) return
    const seo = contentOf(file).seo
    form.content = seo !== undefined ? { seo, ...rest } : { ...rest }
}

function mediaModel(file: string): MediaItem | null {
    const url = seoOf(file).og_image
    return url ? { id: file, url } : null
}

function setOgImage(file: string, media: MediaItem | null) {
    updateSeo(file, { og_image: media?.url ?? '' })
}

function save(file: string) {
    forms[file]?.put(`/admin/page-content/${file}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Pages" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Pages</h1>

    <div class="flex flex-col gap-6 md:flex-row">
        <!-- Page picker -->
        <nav class="flex shrink-0 gap-1 overflow-x-auto md:w-48 md:flex-col md:overflow-visible">
            <button
                v-for="f in pages"
                :key="f.file"
                type="button"
                class="rounded px-3 py-2 text-left text-sm whitespace-nowrap"
                :class="activeFile === f.file ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50'"
                @click="activeFile = f.file"
            >
                {{ f.label }}
            </button>
        </nav>

        <div v-if="activeForm" class="flex-1 space-y-6">
            <AppFormSection title="SEO" description="Shown in search results and social share previews for this page.">
                <AppFormField name="seo-title" label="Meta Title">
                    <template #default="{ id }">
                        <AppInput
                            :id="id"
                            :model-value="seoOf(activeFile).title"
                            placeholder="Falls back to the site name"
                            @update:model-value="(v) => updateSeo(activeFile, { title: v })"
                        />
                    </template>
                </AppFormField>
                <AppFormField name="seo-description" label="Meta Description">
                    <template #default="{ id }">
                        <AppTextarea
                            :id="id"
                            :rows="3"
                            :model-value="seoOf(activeFile).description"
                            placeholder="Falls back to the site tagline"
                            @update:model-value="(v) => updateSeo(activeFile, { description: v })"
                        />
                    </template>
                </AppFormField>
                <AppFormField name="seo-og-image" label="Social Share Image (og:image)">
                    <AppMediaPicker
                        :model-value="mediaModel(activeFile)"
                        label="Choose image"
                        @update:model-value="(m) => setOgImage(activeFile, m)"
                    />
                </AppFormField>
                <AppFormField
                    name="seo-noindex"
                    label="No-index"
                    help="Hides this page from Google and other search engines (adds a noindex meta tag)."
                >
                    <AppSwitch
                        :model-value="seoOf(activeFile).noindex"
                        @update:model-value="(v) => updateSeo(activeFile, { noindex: v })"
                    />
                </AppFormField>
                <AppFormField
                    name="seo-json-ld"
                    label="JSON-LD Schema"
                    help="Raw structured-data JSON. Injected as a <script type=&quot;application/ld+json&quot;> right at the start of this page's body."
                >
                    <template #default="{ id }">
                        <AppTextarea
                            :id="id"
                            :rows="6"
                            :model-value="seoOf(activeFile).json_ld"
                            placeholder='{ &quot;@context&quot;: &quot;https://schema.org&quot;, &quot;@type&quot;: &quot;WebPage&quot; }'
                            @update:model-value="(v) => updateSeo(activeFile, { json_ld: v })"
                        />
                    </template>
                </AppFormField>
            </AppFormSection>

            <AppFormSection title="Content">
                <JsonContentEditor
                    :model-value="restOf(activeFile)"
                    @update:model-value="(v) => updateRest(activeFile, v as JsonObject)"
                />
            </AppFormSection>

            <div class="flex justify-end border-t border-gray-100 pt-4">
                <button
                    type="button"
                    class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="activeForm.processing"
                    @click="save(activeFile)"
                >
                    {{ activeForm.processing ? 'Saving…' : 'Save' }}
                </button>
            </div>
        </div>
    </div>
</template>
