<script setup lang="ts">
import { ref, computed } from 'vue'
import { Head, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
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
    layout: ContentFile[]
}

const props = defineProps<Props>()

// Same pattern as Admin/PageContent/Index.vue: one useForm per file created up
// front (unsaved edits survive switching files), generic kept as
// `Record<string, any>` to avoid feeding a recursive type into useForm's own
// deep conditional types, field named "content" because useForm() reserves "data".
const forms: Record<string, ReturnType<typeof useForm<{ content: Record<string, any> }>>> = {}
props.layout.forEach((f) => {
    forms[f.file] = useForm<{ content: Record<string, any> }>({ content: f.data as Record<string, any> })
})

function contentOf(file: string): JsonObject {
    return (forms[file]?.content ?? {}) as JsonObject
}

const activeFile = ref<string>(props.layout[0]?.file ?? '')
const activeForm = computed(() => forms[activeFile.value] ?? null)

function save(file: string) {
    forms[file]?.put(`/admin/page-content/${file}`, { preserveScroll: true })
}
</script>

<template>
    <Head title="Header / Footer" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Header / Footer</h1>

    <div class="flex flex-col gap-6 md:flex-row">
        <!-- File picker -->
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

        <div v-if="activeForm" class="flex-1 space-y-6">
            <AppFormSection title="Content">
                <JsonContentEditor
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
