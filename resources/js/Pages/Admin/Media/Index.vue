<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import AppFileInput from '@/Components/Atoms/AppFileInput.vue'
import { usePermissions } from '@/Composables/usePermissions'
import { useImageUrl, variantPath, type VariantEntry } from '@/Composables/useImageUrl'

defineOptions({ layout: AdminLayout })

const { can } = usePermissions()
const { toImageUrl } = useImageUrl()

interface MediaItem {
    id: number
    filename: string
    url: string
    variants?: Record<string, VariantEntry> | null
    mime_type: string
    size: number
    width?: number | null
    height?: number | null
    alt_text: string | null
}

interface Props {
    media: Illuminate.LengthAwarePaginator<number, MediaItem>
    filters?: { search?: string; type?: string }
}

interface UploadForm {
    file: File | null
    alt_text: string
}

const props = defineProps<Props>()

const form = useForm<UploadForm>({
    file: null,
    alt_text: '',
})

const search = ref(props.filters?.search ?? '')
const typeFilter = ref(props.filters?.type ?? '')
const selected = ref<number[]>([])
const dragOver = ref(false)

const allIds = computed(() => props.media.data.map((m) => m.id))
const allSelected = computed(
    () => allIds.value.length > 0 && allIds.value.every((id) => selected.value.includes(id)),
)

function thumbSrc(item: MediaItem): string | null {
    const variants = item.variants ?? {}
    return toImageUrl(
        variantPath(variants.thumb) ?? variantPath(variants.md) ?? item.url,
    )
}

function applyFilters() {
    selected.value = []
    router.get('/admin/media', {
        search: search.value || undefined,
        type: typeFilter.value || undefined,
    }, {
        preserveState: true,
        replace: true,
    })
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(applyFilters, 300)
})
watch(typeFilter, applyFilters)

const uploadFile = () => {
    if (!form.file) return
    form.post('/admin/media', {
        forceFormData: true,
        onSuccess: () => {
            form.reset()
        },
        onError: (errors) => {
            // eslint-disable-next-line no-console
            console.error('[media upload] server rejected the upload:', errors)
        },
    })
}

function onFilesSelected(files: File[]) {
    form.file = files[0] ?? null
}

function onDrop(e: DragEvent) {
    dragOver.value = false
    const files = Array.from(e.dataTransfer?.files ?? [])
    if (files[0]) form.file = files[0]
}

const deleteMedia = (id: number) => {
    if (confirm('Delete this file?')) {
        router.delete(`/admin/media/${id}`)
        selected.value = selected.value.filter((sid) => sid !== id)
    }
}

function toggleSelect(id: number) {
    selected.value = selected.value.includes(id)
        ? selected.value.filter((sid) => sid !== id)
        : [...selected.value, id]
}

function toggleSelectAll() {
    selected.value = allSelected.value ? [] : [...allIds.value]
}

function bulkDelete() {
    if (!selected.value.length) return
    if (!confirm(`Delete ${selected.value.length} selected file(s)?`)) return
    router.post('/admin/media/bulk-destroy', { ids: selected.value }, {
        onSuccess: () => { selected.value = [] },
    })
}

const editingItem = ref<MediaItem | null>(null)
const editForm = useForm({ alt_text: '' })

const openEdit = (item: MediaItem) => {
    if (!can('media.update')) return
    editingItem.value = item
    editForm.clearErrors()
    editForm.alt_text = item.alt_text ?? ''
}

const closeEdit = () => {
    editingItem.value = null
}

const saveAltText = () => {
    if (!editingItem.value) return
    editForm.put(`/admin/media/${editingItem.value.id}`, {
        preserveScroll: true,
        onSuccess: () => closeEdit(),
    })
}

const formatSize = (bytes: number) => {
    if (bytes < 1024) return bytes + ' B'
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB'
    return (bytes / 1048576).toFixed(1) + ' MB'
}
</script>

<template>
    <Head title="Media" />
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-900">Media Library</h1>
        <button
            v-if="selected.length && can('media.delete')"
            type="button"
            class="rounded-md bg-rose-600 px-4 py-2 text-sm font-medium text-white hover:bg-rose-700"
            @click="bulkDelete"
        >
            Delete selected ({{ selected.length }})
        </button>
    </div>

    <div class="mb-6 rounded-lg bg-white p-6 shadow">
        <form @submit.prevent="uploadFile" class="space-y-4">
            <div
                class="flex w-full min-w-0 flex-col items-center gap-3 rounded-lg border-2 border-dashed px-4 py-8 text-center transition-colors"
                :class="dragOver
                    ? 'border-brand-500 bg-brand-600/10'
                    : 'border-gray-300 bg-gray-50'"
                @dragover.prevent="dragOver = true"
                @dragleave.prevent="dragOver = false"
                @drop.prevent="onDrop"
            >
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-500">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                    </svg>
                </div>
                <div class="space-y-1">
                    <p class="text-sm font-medium text-gray-800">Upload media</p>
                    <p class="text-xs text-gray-500">Drag &amp; drop or choose a file</p>
                    <p class="text-xs text-gray-400">JPEG, PNG, WebP, GIF, or PDF. Max 10 MB.</p>
                </div>
                <div class="flex flex-wrap items-center justify-center gap-2">
                    <AppFileInput
                        accept="image/jpeg,image/png,image/webp,image/gif,application/pdf"
                        :disabled="form.processing"
                        label="Choose file"
                        variant="primary"
                        @select="onFilesSelected"
                    />
                    <button
                        type="submit"
                        :disabled="form.processing || !form.file"
                        class="inline-flex items-center justify-center rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        {{ form.processing ? 'Uploading…' : 'Upload' }}
                    </button>
                </div>
                <p v-if="form.file" class="max-w-full truncate text-xs text-gray-600">
                    Selected: {{ form.file.name }}
                </p>
            </div>

            <label class="block text-xs font-medium text-gray-500">
                Alt text
                <input
                    v-model="form.alt_text"
                    type="text"
                    placeholder="Describe the image…"
                    class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900"
                >
            </label>
            <p
                v-if="form.file && !form.alt_text.trim() && form.file.type.startsWith('image/')"
                class="text-xs text-amber-600"
            >
                Tip: add alt text — empty alt hurts accessibility and SEO.
            </p>
        </form>
        <div v-if="form.progress" class="mt-3">
            <div class="h-2 w-full rounded-full bg-gray-200">
                <div class="h-2 rounded-full bg-brand-600" :style="{ width: form.progress.percentage + '%' }" />
            </div>
        </div>
        <div
            v-if="Object.keys(form.errors).length"
            class="mt-3 rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700"
        >
            <p class="font-medium">Upload failed:</p>
            <ul class="mt-1 list-disc pl-5">
                <li v-for="(msg, field) in form.errors" :key="field">
                    <span class="font-mono text-xs text-rose-500">{{ field }}:</span> {{ msg }}
                </li>
            </ul>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-3">
        <input
            v-model="search"
            type="search"
            placeholder="Search filename or alt text…"
            class="min-w-[200px] flex-1 rounded-md border border-gray-300 px-3 py-2 text-sm"
        >
        <select v-model="typeFilter" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
            <option value="">All types</option>
            <option value="image">Images</option>
            <option value="pdf">PDFs</option>
        </select>
        <label v-if="media.data.length" class="flex items-center gap-2 text-sm text-gray-600">
            <input type="checkbox" :checked="allSelected" class="rounded border-gray-300" @change="toggleSelectAll">
            Select page
        </label>
    </div>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4 lg:grid-cols-6">
        <div v-for="item in media.data" :key="item.id" class="group overflow-hidden rounded-lg bg-white shadow">
            <div class="relative">
                <label class="absolute left-2 top-2 z-10">
                    <input
                        type="checkbox"
                        class="rounded border-gray-300"
                        :checked="selected.includes(item.id)"
                        @change="toggleSelect(item.id)"
                    >
                </label>
                <button
                    type="button"
                    class="flex aspect-square w-full items-center justify-center overflow-hidden bg-gray-100"
                    :class="can('media.update') ? 'cursor-pointer' : 'cursor-default'"
                    @click="openEdit(item)"
                >
                    <img
                        v-if="item.mime_type.startsWith('image/') && thumbSrc(item)"
                        :src="thumbSrc(item)!"
                        :alt="item.alt_text ?? ''"
                        class="h-full w-full object-cover"
                        loading="lazy"
                        decoding="async"
                    >
                    <div v-else class="p-2 text-center">
                        <div class="mb-1 text-2xl text-gray-400">&#128196;</div>
                        <p class="truncate text-xs text-gray-500">{{ item.filename }}</p>
                    </div>
                </button>
            </div>
            <div class="p-2">
                <p class="truncate text-xs text-gray-900">{{ item.filename }}</p>
                <p class="text-xs text-gray-400">{{ formatSize(item.size) }}</p>
                <div class="mt-1 flex items-center gap-2">
                    <button v-if="can('media.update')" type="button" class="text-xs text-gray-500 hover:text-gray-700" @click="openEdit(item)">Edit</button>
                    <button v-if="can('media.delete')" type="button" class="text-xs text-red-500 hover:text-red-700" @click="deleteMedia(item.id)">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <Teleport to="body">
        <div
            v-if="editingItem"
            class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeEdit"
        >
            <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold text-gray-900">Edit Media</h2>
                <div class="mb-4 flex aspect-video items-center justify-center overflow-hidden rounded-md bg-gray-100">
                    <img
                        v-if="editingItem.mime_type.startsWith('image/') && thumbSrc(editingItem)"
                        :src="thumbSrc(editingItem)!"
                        :alt="editingItem.alt_text ?? ''"
                        class="h-full w-full object-contain"
                    >
                    <p v-else class="truncate px-4 text-sm text-gray-500">{{ editingItem.filename }}</p>
                </div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Alt Text</label>
                <input
                    v-model="editForm.alt_text"
                    type="text"
                    placeholder="Describe the image…"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                    @keyup.enter="saveAltText"
                >
                <p v-if="editForm.errors.alt_text" class="mt-1 text-xs text-rose-600">{{ editForm.errors.alt_text }}</p>
                <div class="mt-4 flex justify-end gap-2">
                    <button type="button" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900" @click="closeEdit">Cancel</button>
                    <button
                        type="button"
                        :disabled="editForm.processing"
                        class="rounded-md bg-gray-900 px-4 py-1.5 text-sm text-white hover:bg-gray-800 disabled:opacity-50"
                        @click="saveAltText"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <div v-if="media.links && media.links.length > 3" class="mt-6 flex justify-center">
        <nav class="flex space-x-1">
            <template v-for="(link, i) in media.links" :key="`${link.url ?? ''}-${i}`">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    v-html="link.label"
                    :class="[
                        'rounded px-3 py-1 text-sm',
                        link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100',
                    ]"
                />
                <span
                    v-else
                    v-html="link.label"
                    class="cursor-not-allowed rounded px-3 py-1 text-sm opacity-50"
                />
            </template>
        </nav>
    </div>
</template>
