<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref } from 'vue'
import { usePermissions } from '@/Composables/usePermissions'

defineOptions({ layout: AdminLayout })

const { can } = usePermissions()

interface MediaItem {
    id: number
    filename: string
    url: string
    mime_type: string
    size: number
    alt_text: string | null
}

interface Props {
    media: Illuminate.LengthAwarePaginator<number, MediaItem>
}

interface UploadForm {
    file: File | null
    alt_text: string
}

defineProps<Props>()

const form = useForm<UploadForm>({
    file: null,
    alt_text: '',
})

const fileInput = ref<HTMLInputElement | null>(null)

const uploadFile = () => {
    form.post('/admin/media', {
        forceFormData: true,
        onSuccess: () => {
            form.reset()
            if (fileInput.value) fileInput.value.value = ''
        },
        // Log to console too — the ONE UI-visible error slot is easy to miss
        // when the file input is right beside it. Console gives the raw shape.
        onError: (errors) => {
            // eslint-disable-next-line no-console
            console.error('[media upload] server rejected the upload:', errors)
        },
    })
}

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement
    form.file = target.files?.[0] ?? null
}

const deleteMedia = (id: number) => {
    if (confirm('Delete this file?')) {
        router.delete(`/admin/media/${id}`)
    }
}

// Alt text was previously upload-only — the `media.update` permission was
// declared (config/modules.php) but had no route/UI behind it, so a typo
// could never be fixed without re-uploading. See feedback.md §33.
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
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Media Library</h1>

    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <form @submit.prevent="uploadFile" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">File</label>
                <input ref="fileInput" type="file" @change="onFileChange" required class="w-full text-sm" />
                <p class="mt-1 text-xs text-gray-400">
                    JPEG, PNG, WebP, GIF, or PDF. Max 10 MB.
                </p>
            </div>
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Alt Text</label>
                <input v-model="form.alt_text" type="text" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800 disabled:opacity-50">
                Upload
            </button>
        </form>
        <div v-if="form.progress" class="mt-2">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-gray-900 h-2 rounded-full" :style="{ width: form.progress.percentage + '%' }" />
            </div>
        </div>
        <!-- Surface every field error the server returned. Without this the
             upload silently fails (loading bar drops, no message, no new
             row) — the actual reason was buried in form.errors. -->
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

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div v-for="item in media.data" :key="item.id" class="bg-white rounded-lg shadow overflow-hidden group">
            <button
                type="button"
                class="aspect-square w-full bg-gray-100 flex items-center justify-center overflow-hidden"
                :class="can('media.update') ? 'cursor-pointer' : 'cursor-default'"
                @click="openEdit(item)"
            >
                <img v-if="item.mime_type.startsWith('image/')" :src="item.url" :alt="item.alt_text ?? ''" class="w-full h-full object-cover" />
                <div v-else class="text-center p-2">
                    <div class="text-2xl text-gray-400 mb-1">&#128196;</div>
                    <p class="text-xs text-gray-500 truncate">{{ item.filename }}</p>
                </div>
            </button>
            <div class="p-2">
                <p class="text-xs text-gray-900 truncate">{{ item.filename }}</p>
                <p class="text-xs text-gray-400">{{ formatSize(item.size) }}</p>
                <div class="flex items-center gap-2 mt-1">
                    <button v-if="can('media.update')" @click="openEdit(item)" class="text-xs text-gray-500 hover:text-gray-700">Edit</button>
                    <button @click="deleteMedia(item.id)" class="text-xs text-red-500 hover:text-red-700">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Alt-text edit modal. Teleported to escape the grid's own stacking
         context, same z-index tier as AppMediaPicker's library overlay. -->
    <Teleport to="body">
        <div
            v-if="editingItem"
            class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4"
            @click.self="closeEdit"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-sm p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">Edit Media</h2>
                <div class="aspect-video bg-gray-100 rounded-md overflow-hidden mb-4 flex items-center justify-center">
                    <img
                        v-if="editingItem.mime_type.startsWith('image/')"
                        :src="editingItem.url"
                        :alt="editingItem.alt_text ?? ''"
                        class="w-full h-full object-contain"
                    />
                    <p v-else class="text-sm text-gray-500 px-4 truncate">{{ editingItem.filename }}</p>
                </div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Alt Text</label>
                <input
                    v-model="editForm.alt_text"
                    type="text"
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                    @keyup.enter="saveAltText"
                />
                <p v-if="editForm.errors.alt_text" class="mt-1 text-xs text-rose-600">{{ editForm.errors.alt_text }}</p>
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="closeEdit" class="px-3 py-1.5 text-sm text-gray-600 hover:text-gray-900">Cancel</button>
                    <button
                        @click="saveAltText"
                        :disabled="editForm.processing"
                        class="px-4 py-1.5 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800 disabled:opacity-50"
                    >
                        Save
                    </button>
                </div>
            </div>
        </div>
    </Teleport>

    <!-- Pagination -->
    <div v-if="media.links && media.links.length > 3" class="mt-6 flex justify-center">
        <nav class="flex space-x-1">
            <Link
                v-for="link in media.links"
                :key="link.label"
                :href="link.url || '#'"
                v-html="link.label"
                :class="[
                    'px-3 py-1 text-sm rounded',
                    link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100',
                    !link.url ? 'opacity-50 cursor-not-allowed' : ''
                ]"
            />
        </nav>
    </div>
</template>
