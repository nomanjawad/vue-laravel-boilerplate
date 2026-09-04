<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'

defineOptions({ layout: AdminLayout })

interface MediaItem {
    id: number | string
    url?: string | null
}

interface FooterColumn {
    title: string
    type: string
}

interface HeaderContent {
    logo: string
    logo_alt: string
    show_cta_button: boolean
    cta_text: string
    cta_url: string
}

interface FooterContent {
    columns: FooterColumn[]
    copyright: string
    show_social_icons: boolean
}

interface ContentFile {
    file: string
    label: string
    data: Record<string, unknown>
}

interface Props {
    layout: ContentFile[]
}

const props = defineProps<Props>()

const COLUMN_TYPES = [
    { value: 'menu', label: 'Menu links' },
    { value: 'contact_info', label: 'Contact info' },
    { value: 'social', label: 'Social links' },
]

function asHeader(data: Record<string, unknown>): HeaderContent {
    return {
        logo: typeof data.logo === 'string' ? data.logo : '',
        logo_alt: typeof data.logo_alt === 'string' ? data.logo_alt : '',
        show_cta_button: Boolean(data.show_cta_button),
        cta_text: typeof data.cta_text === 'string' ? data.cta_text : '',
        cta_url: typeof data.cta_url === 'string' ? data.cta_url : '',
    }
}

function asFooter(data: Record<string, unknown>): FooterContent {
    const columns = Array.isArray(data.columns)
        ? data.columns.map((c) => {
            const row = (c && typeof c === 'object') ? c as Record<string, unknown> : {}
            return {
                title: typeof row.title === 'string' ? row.title : '',
                type: typeof row.type === 'string' ? row.type : 'menu',
            }
        })
        : []

    return {
        columns,
        copyright: typeof data.copyright === 'string' ? data.copyright : '',
        show_social_icons: data.show_social_icons !== false,
    }
}

const forms: Record<string, ReturnType<typeof useForm<{ content: Record<string, any> }>>> = {}
props.layout.forEach((f) => {
    const content = f.file === 'header'
        ? asHeader(f.data)
        : f.file === 'footer'
            ? asFooter(f.data)
            : { ...f.data }
    forms[f.file] = useForm<{ content: Record<string, any> }>({ content })
})

const activeFile = ref<string>(props.layout[0]?.file ?? '')
const activeForm = computed(() => forms[activeFile.value] ?? null)

const headerContent = computed(() => forms.header?.content as HeaderContent | undefined)
const footerContent = computed(() => forms.footer?.content as FooterContent | undefined)

function logoModel(): MediaItem | null {
    const url = headerContent.value?.logo
    return url ? { id: 'header-logo', url } : null
}

function setLogo(media: MediaItem | null) {
    if (!forms.header) return
    forms.header.content.logo = media?.url ?? ''
}

function addColumn() {
    if (!forms.footer) return
    const cols = (forms.footer.content.columns as FooterColumn[]) ?? []
    forms.footer.content.columns = [...cols, { title: 'New column', type: 'menu' }]
}

function removeColumn(index: number) {
    if (!forms.footer) return
    const cols = [...((forms.footer.content.columns as FooterColumn[]) ?? [])]
    cols.splice(index, 1)
    forms.footer.content.columns = cols
}

function moveColumn(index: number, dir: -1 | 1) {
    if (!forms.footer) return
    const cols = [...((forms.footer.content.columns as FooterColumn[]) ?? [])]
    const next = index + dir
    if (next < 0 || next >= cols.length) return
    const [row] = cols.splice(index, 1)
    if (!row) return
    cols.splice(next, 0, row)
    forms.footer.content.columns = cols
}

function save(file: string) {
    forms[file]?.put(`/admin/page-content/${file}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Header & Footer" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Header & Footer</h1>

    <div class="flex flex-col gap-6 md:flex-row">
        <nav class="flex shrink-0 gap-1 overflow-x-auto md:w-48 md:flex-col md:overflow-visible">
            <button
                v-for="f in layout"
                :key="f.file"
                type="button"
                class="rounded px-3 py-2 text-left text-sm whitespace-nowrap"
                :class="activeFile === f.file ? 'bg-indigo-50 font-medium text-indigo-700' : 'text-gray-600 hover:bg-gray-50'"
                @click="activeFile = f.file"
            >
                {{ f.label }}
            </button>
        </nav>

        <div v-if="activeForm && activeFile === 'header' && headerContent" class="flex-1 space-y-6">
            <AppFormSection title="Header" description="Logo and call-to-action shown in the public site header.">
                <AppFormField name="content.logo" label="Logo">
                    <AppMediaPicker
                        :model-value="logoModel()"
                        label="Choose logo"
                        @update:model-value="setLogo"
                    />
                    <p class="mt-1 text-xs text-gray-500">
                        Falls back behind Site Settings → Logo when that is set.
                    </p>
                </AppFormField>

                <AppFormField name="content.logo_alt" label="Logo alt text">
                    <AppInput v-model="headerContent.logo_alt" placeholder="Company name" />
                </AppFormField>

                <AppFormField name="content.show_cta_button" label="Show CTA button">
                    <AppSwitch v-model="headerContent.show_cta_button" />
                </AppFormField>

                <AppFormField name="content.cta_text" label="CTA label">
                    <AppInput v-model="headerContent.cta_text" placeholder="Get Quote" />
                </AppFormField>

                <AppFormField name="content.cta_url" label="CTA URL">
                    <AppInput v-model="headerContent.cta_url" placeholder="/contact" />
                </AppFormField>
            </AppFormSection>

            <div class="flex justify-end border-t border-gray-100 pt-4">
                <button
                    type="button"
                    class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="activeForm.processing"
                    @click="save('header')"
                >
                    {{ activeForm.processing ? 'Saving…' : 'Save header' }}
                </button>
            </div>
        </div>

        <div v-else-if="activeForm && activeFile === 'footer' && footerContent" class="flex-1 space-y-6">
            <AppFormSection title="Footer columns" description="Each column picks what to render (menu, contact, or social).">
                <div class="space-y-3">
                    <div
                        v-for="(col, index) in footerContent.columns"
                        :key="index"
                        class="rounded-lg border border-gray-200 p-4 space-y-3"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs font-medium uppercase tracking-wide text-gray-500">Column {{ index + 1 }}</span>
                            <div class="flex gap-1">
                                <button type="button" class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100" @click="moveColumn(index, -1)">↑</button>
                                <button type="button" class="rounded px-2 py-1 text-xs text-gray-500 hover:bg-gray-100" @click="moveColumn(index, 1)">↓</button>
                                <button type="button" class="rounded px-2 py-1 text-xs text-red-600 hover:bg-red-50" @click="removeColumn(index)">Remove</button>
                            </div>
                        </div>
                        <AppFormField :name="`content.columns.${index}.title`" label="Title">
                            <AppInput v-model="col.title" placeholder="Company" />
                        </AppFormField>
                        <AppFormField :name="`content.columns.${index}.type`" label="Type">
                            <AppSelect v-model="col.type" :options="COLUMN_TYPES" />
                        </AppFormField>
                    </div>

                    <button
                        type="button"
                        class="rounded border border-dashed border-gray-300 px-3 py-2 text-sm text-gray-600 hover:border-gray-400 hover:text-gray-900"
                        @click="addColumn"
                    >
                        + Add column
                    </button>
                </div>
            </AppFormSection>

            <AppFormSection title="Footer meta">
                <AppFormField name="content.copyright" label="Copyright" help="Tokens: {year}, {site_name}">
                    <AppInput v-model="footerContent.copyright" placeholder="© {year} {site_name}. All rights reserved." />
                </AppFormField>
                <AppFormField name="content.show_social_icons" label="Show social icons">
                    <AppSwitch v-model="footerContent.show_social_icons" />
                </AppFormField>
            </AppFormSection>

            <div class="flex justify-end border-t border-gray-100 pt-4">
                <button
                    type="button"
                    class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                    :disabled="activeForm.processing"
                    @click="save('footer')"
                >
                    {{ activeForm.processing ? 'Saving…' : 'Save footer' }}
                </button>
            </div>
        </div>
    </div>
</template>
