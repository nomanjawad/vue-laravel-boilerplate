<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
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

// Inertia's useForm() reserves the key "data" (it's a method on the form
// instance), so the form field holding the JSON payload is named "content"
// instead — the backend route reads `content` from the request body to match.

interface Props {
    pages: ContentFile[]
    layout: ContentFile[]
}

const props = defineProps<Props>()

interface SeoBlock {
    title: string
    description: string
    og_image: string
}

interface MediaItem {
    id: number | string
    url?: string | null
}

// One useForm per editable file, created once up front — switching between
// tabs/sub-tabs only changes which form is *displayed*, so unsaved edits in
// a form that's currently hidden are never lost.
//
// The form's generic is kept as `Record<string, any>` rather than the
// recursive JsonObject type — feeding a recursive type into useForm's own
// (already-deep) conditional types blows up the TS compiler ("Type
// instantiation is excessively deep"). Helper functions below cast back to
// JsonObject at the point of use instead.
const forms: Record<string, ReturnType<typeof useForm<{ content: Record<string, any> }>>> = {}
;[...props.pages, ...props.layout].forEach((f) => {
    forms[f.file] = useForm<{ content: Record<string, any> }>({ content: f.data as Record<string, any> })
})

function contentOf(file: string): JsonObject {
    return (forms[file]?.content ?? {}) as JsonObject
}

type Section = 'pages' | 'layout'
const activeSection = ref<Section>('pages')
const activePageFile = ref<string>(props.pages[0]?.file ?? '')
const activeLayoutFile = ref<string>(props.layout[0]?.file ?? '')

const activeFiles = computed<ContentFile[]>(() => (activeSection.value === 'pages' ? props.pages : props.layout))
const activeFile = computed<string>(() => (activeSection.value === 'pages' ? activePageFile.value : activeLayoutFile.value))
const activeForm = computed(() => forms[activeFile.value] ?? null)

function selectFile(file: string) {
    if (activeSection.value === 'pages') activePageFile.value = file
    else activeLayoutFile.value = file
}

// Pages carry an `seo` block inside their JSON; header/footer don't. Splitting
// it out lets SEO use dedicated inputs (title/description/og_image via the
// media picker) while the rest of the page's JSON goes through the generic
// recursive editor.
function seoOf(file: string): SeoBlock {
    const seo = (contentOf(file).seo ?? {}) as Partial<SeoBlock>
    return {
        title: seo.title ?? '',
        description: seo.description ?? '',
        og_image: seo.og_image ?? '',
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
    <Head title="Page Content" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Page Content</h1>

    <!-- Section tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6" role="tablist" aria-label="Page content sections">
            <button
                type="button"
                role="tab"
                :aria-selected="activeSection === 'pages'"
                class="border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                :class="activeSection === 'pages'
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                @click="activeSection = 'pages'"
            >
                Pages
            </button>
            <button
                type="button"
                role="tab"
                :aria-selected="activeSection === 'layout'"
                class="border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                :class="activeSection === 'layout'
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                @click="activeSection = 'layout'"
            >
                Header / Footer
            </button>
        </nav>
    </div>

    <div class="flex flex-col gap-6 md:flex-row">
        <!-- File picker within the active section -->
        <nav class="flex shrink-0 gap-1 overflow-x-auto md:w-48 md:flex-col md:overflow-visible">
            <button
                v-for="f in activeFiles"
                :key="f.file"
                type="button"
                class="rounded px-3 py-2 text-left text-sm whitespace-nowrap"
                :class="activeFile === f.file ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50'"
                @click="selectFile(f.file)"
            >
                {{ f.label }}
            </button>
        </nav>

        <div v-if="activeForm" class="flex-1 space-y-6">
            <AppFormSection v-if="activeSection === 'pages'" title="SEO" description="Shown in search results and social share previews for this page.">
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
            </AppFormSection>

            <AppFormSection title="Content">
                <JsonContentEditor
                    v-if="activeSection === 'pages'"
                    :model-value="restOf(activeFile)"
                    @update:model-value="(v) => updateRest(activeFile, v as JsonObject)"
                />
                <JsonContentEditor
                    v-else
                    :model-value="contentOf(activeFile)"
                    @update:model-value="(v) => { activeForm!.content = v as JsonObject }"
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
