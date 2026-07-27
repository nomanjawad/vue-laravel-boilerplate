<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router, Link } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

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
    </div>

    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        <div v-for="item in media.data" :key="item.id" class="bg-white rounded-lg shadow overflow-hidden group">
            <div class="aspect-square bg-gray-100 flex items-center justify-center overflow-hidden">
                <img v-if="item.mime_type.startsWith('image/')" :src="item.url" :alt="item.alt_text ?? ''" class="w-full h-full object-cover" />
                <div v-else class="text-center p-2">
                    <div class="text-2xl text-gray-400 mb-1">&#128196;</div>
                    <p class="text-xs text-gray-500 truncate">{{ item.filename }}</p>
                </div>
            </div>
            <div class="p-2">
                <p class="text-xs text-gray-900 truncate">{{ item.filename }}</p>
                <p class="text-xs text-gray-400">{{ formatSize(item.size) }}</p>
                <button @click="deleteMedia(item.id)" class="text-xs text-red-500 hover:text-red-700 mt-1">Delete</button>
            </div>
        </div>
    </div>

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
