<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import AppFileInput from '@/Components/Atoms/AppFileInput.vue'
import AppSpinner from '@/Components/Atoms/AppSpinner.vue'
import { useImageUrl, variantPath, type VariantEntry } from '@/Composables/useImageUrl'

interface MediaItem {
    id: number | string
    url?: string | null
    variants?: Record<string, VariantEntry> | null
    alt_text?: string | null
    filename?: string
    mime_type?: string
    width?: number | null
    height?: number | null
}

interface LibraryPage {
    data: MediaItem[]
    current_page: number
    last_page: number
    links?: { url: string | null; label: string; active: boolean }[]
}

interface Props {
    // The bound value: a media row { id, url, variants, alt_text } or its id.
    modelValue?: MediaItem | number | string | null
    accept?: string
    label?: string
    uploadUrl?: string
    /** When true, only images appear in the library browser. */
    imagesOnly?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: null,
    accept: 'image/*',
    label: 'Upload image',
    uploadUrl: '/admin/media',
    imagesOnly: true,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: MediaItem | null): void
}>()

const { toImageUrl } = useImageUrl()

const uploading = ref(false)
const dragOver = ref(false)
const error = ref<string | null>(null)
const altText = ref('')
const altNudge = ref(false)
const libraryOpen = ref(false)
const libraryLoading = ref(false)
const libraryError = ref<string | null>(null)
const librarySearch = ref('')
const libraryPage = ref<LibraryPage | null>(null)

const preview = computed<string | null>(() => {
    if (!props.modelValue) return null
    if (typeof props.modelValue === 'object') {
        const variants = props.modelValue.variants ?? {}
        return toImageUrl(
            variantPath(variants.thumb)
                ?? variantPath(variants.md)
                ?? props.modelValue.url
                ?? '',
        )
    }
    return null
})

function thumbUrl(item: MediaItem): string | null {
    const variants = item.variants ?? {}
    return toImageUrl(
        variantPath(variants.thumb) ?? variantPath(variants.md) ?? item.url ?? '',
    )
}

async function upload(files: File[] | FileList | null | undefined) {
    if (!files || !files.length) return
    const file = files[0]
    if (!file) return

    // Nudge when alt is empty — images need alt for a11y / SEO (Ahrefs hygiene).
    if (!altText.value.trim() && file.type.startsWith('image/')) {
        altNudge.value = true
    } else {
        altNudge.value = false
    }

    uploading.value = true
    error.value = null

    const formData = new FormData()
    formData.append('file', file)
    if (altText.value.trim()) {
        formData.append('alt_text', altText.value.trim())
    }

    // Use Inertia's router so the response auto-applies session flashes/errors.
    router.post(props.uploadUrl, formData, {
        forceFormData: true,
        preserveScroll: true,
        preserveState: true,
        onSuccess: (page) => {
            const flash = (page.props as { flash?: { media?: MediaItem | null } }).flash
            const media = flash?.media ?? null
            if (media) emit('update:modelValue', media)
            altText.value = ''
            altNudge.value = false
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

function openLibrary() {
    libraryOpen.value = true
    librarySearch.value = ''
    fetchLibrary(1)
}

function closeLibrary() {
    libraryOpen.value = false
}

async function fetchLibrary(page = 1) {
    libraryLoading.value = true
    libraryError.value = null
    try {
        const params = new URLSearchParams({
            format: 'json',
            page: String(page),
        })
        if (librarySearch.value.trim()) {
            params.set('search', librarySearch.value.trim())
        }
        if (props.imagesOnly) {
            params.set('type', 'image')
        }
        const res = await fetch(`/admin/media?${params.toString()}`, {
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            credentials: 'same-origin',
        })
        if (!res.ok) throw new Error(`Failed to load library (${res.status})`)
        libraryPage.value = await res.json() as LibraryPage
    } catch (e) {
        libraryError.value = e instanceof Error ? e.message : 'Failed to load library'
        libraryPage.value = null
    } finally {
        libraryLoading.value = false
    }
}

let searchTimer: ReturnType<typeof setTimeout> | null = null
watch(librarySearch, () => {
    if (!libraryOpen.value) return
    if (searchTimer) clearTimeout(searchTimer)
    searchTimer = setTimeout(() => fetchLibrary(1), 300)
})

function selectFromLibrary(item: MediaItem) {
    emit('update:modelValue', item)
    closeLibrary()
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

            <div class="min-w-0 flex-1">
                <p class="text-sm text-gray-700">{{ label }}</p>
                <p class="text-xs text-gray-500">Drag &amp; drop, upload, or choose from the library.</p>
                <label class="mt-2 block text-xs font-medium text-gray-500">
                    Alt text
                    <input
                        v-model="altText"
                        type="text"
                        placeholder="Describe the image for accessibility…"
                        class="mt-1 w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm"
                        @click.stop
                    >
                </label>
                <p v-if="altNudge" class="mt-1 text-xs text-amber-600">
                    Tip: add alt text — empty alt hurts accessibility and SEO.
                </p>
                <p v-if="error" class="mt-1 text-xs text-rose-600">{{ error }}</p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    type="button"
                    class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                    @click="openLibrary"
                >
                    Choose from library
                </button>
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

        <Teleport to="body">
            <div
                v-if="libraryOpen"
                class="fixed inset-0 z-[200] flex items-center justify-center bg-black/50 p-4"
                @click.self="closeLibrary"
            >
                <div class="flex max-h-[85vh] w-full max-w-3xl flex-col rounded-lg bg-white shadow-xl">
                    <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                        <h2 class="text-lg font-semibold text-gray-900">Media library</h2>
                        <button type="button" class="text-sm text-gray-500 hover:text-gray-800" @click="closeLibrary">
                            Close
                        </button>
                    </div>
                    <div class="border-b border-gray-100 px-5 py-3">
                        <input
                            v-model="librarySearch"
                            type="search"
                            placeholder="Search by filename or alt text…"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                        >
                    </div>
                    <div class="min-h-0 flex-1 overflow-y-auto p-5">
                        <div v-if="libraryLoading" class="flex justify-center py-12">
                            <AppSpinner :size="28" />
                        </div>
                        <p v-else-if="libraryError" class="py-8 text-center text-sm text-rose-600">{{ libraryError }}</p>
                        <p
                            v-else-if="!libraryPage?.data?.length"
                            class="py-8 text-center text-sm text-gray-500"
                        >
                            No media found.
                        </p>
                        <div v-else class="grid grid-cols-3 gap-3 sm:grid-cols-4 md:grid-cols-5">
                            <button
                                v-for="item in libraryPage.data"
                                :key="item.id"
                                type="button"
                                class="group overflow-hidden rounded-lg border border-gray-200 bg-gray-50 text-left transition hover:border-indigo-400"
                                @click="selectFromLibrary(item)"
                            >
                                <div class="aspect-square overflow-hidden bg-gray-100">
                                    <img
                                        v-if="thumbUrl(item)"
                                        :src="thumbUrl(item)!"
                                        :alt="item.alt_text ?? item.filename ?? ''"
                                        class="h-full w-full object-cover"
                                        loading="lazy"
                                        decoding="async"
                                    >
                                    <div v-else class="flex h-full items-center justify-center text-xs text-gray-400">
                                        file
                                    </div>
                                </div>
                                <p class="truncate px-2 py-1.5 text-xs text-gray-600">{{ item.filename }}</p>
                            </button>
                        </div>
                    </div>
                    <div
                        v-if="libraryPage && libraryPage.last_page > 1"
                        class="flex justify-center gap-1 border-t border-gray-100 px-5 py-3"
                    >
                        <button
                            v-for="n in libraryPage.last_page"
                            :key="n"
                            type="button"
                            class="rounded px-2.5 py-1 text-xs"
                            :class="n === libraryPage.current_page ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100'"
                            @click="fetchLibrary(n)"
                        >
                            {{ n }}
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
