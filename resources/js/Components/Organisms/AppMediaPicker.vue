<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import AppFileInput from '@/Components/Atoms/AppFileInput.vue'
import AppSpinner from '@/Components/Atoms/AppSpinner.vue'
import { useImageUrl } from '@/Composables/useImageUrl'

interface MediaItem {
    id: number | string
    url?: string | null
    variants?: Record<string, string | undefined> | null
    alt_text?: string | null
}

interface Props {
    // The bound value: a media row { id, url, variants, alt_text } or its id.
    modelValue?: MediaItem | number | string | null
    accept?: string
    label?: string
    uploadUrl?: string
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    accept: 'image/*',
    label: 'Upload image',
    uploadUrl: '/admin/media',
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaItem | null): void
}>()

const { toImageUrl } = useImageUrl()

const uploading = ref(false)
const dragOver = ref(false)
const error = ref<string | null>(null)

const preview = computed<string | null>(() => {
    if (!props.modelValue) return null
    if (typeof props.modelValue === 'object') {
        const variants = props.modelValue.variants ?? {}
        return toImageUrl(variants.thumb ?? variants.md ?? props.modelValue.url ?? '')
    }
    return null
})

async function upload(files: File[] | FileList | null | undefined) {
    if (!files || !files.length) return
    const file = files[0]
    if (!file) return
    uploading.value = true
    error.value = null

    const formData = new FormData()
    formData.append('file', file)

    // Use Inertia's router so the response auto-applies session flashes/errors.
    router.post(props.uploadUrl, formData, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            const flash = (page.props as { flash?: { media?: MediaItem | null } }).flash
            const media = flash?.media ?? null
            if (media) emit('update:modelValue', media)
        },
        onError: (errors: Record<string, string | string[]>) => {
            error.value = Object.values(errors).flat().join(' ')
        },
        onFinish: () => { uploading.value = false },
    })
}

function clear() {
    emit('update:modelValue', null)
}

function onDrop(e: DragEvent) {
    dragOver.value = false
    upload(Array.from(e.dataTransfer?.files ?? []))
}
</script>

<template>
    <div>
        <div
            class="flex items-center gap-3 rounded-lg border-2 border-dashed p-3"
            :class="dragOver ? 'border-indigo-400 bg-indigo-50' : 'border-gray-300 bg-white'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
        >
            <div
                v-if="preview"
                class="h-16 w-16 overflow-hidden rounded border border-gray-200 bg-gray-100"
            >
                <img :src="preview" class="h-full w-full object-cover" alt="">
            </div>
            <div v-else class="flex h-16 w-16 items-center justify-center rounded border border-gray-200 bg-gray-100 text-gray-400">
                <AppSpinner v-if="uploading" :size="20" />
                <span v-else class="text-xs">no image</span>
            </div>

            <div class="flex-1">
                <p class="text-sm text-gray-700">{{ label }}</p>
                <p class="text-xs text-gray-500">Drag &amp; drop or pick a file. WebP variants are generated automatically.</p>
                <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
            </div>

            <AppFileInput :accept="accept" @select="upload" />
            <button
                v-if="preview"
                type="button"
                class="text-xs text-rose-600 hover:underline"
                @click="clear"
            >
                Remove
            </button>
        </div>
    </div>
</template>
