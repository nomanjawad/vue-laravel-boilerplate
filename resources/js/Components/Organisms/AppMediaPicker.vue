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
const multiFileWarn = ref(false)

const selected = computed<MediaItem | null>(() => {
    if (!props.modelValue || typeof props.modelValue !== 'object') return null
    return props.modelValue
})

const preview = computed<string | null>(() => {
    if (!selected.value) return null
    const variants = selected.value.variants ?? {}
    return toImageUrl(
        variantPath(variants.thumb)
            ?? variantPath(variants.md)
            ?? selected.value.url
            ?? '',
    )
})

const caption = computed(() => {
    if (!selected.value) return ''
    const name = selected.value.filename ?? 'Selected file'
    const w = selected.value.width
    const h = selected.value.height
    if (w && h) return `${name} · ${w}×${h}`
    return name
})

function thumbUrl(item: MediaItem): string | null {
    const variants = item.variants ?? {}
    return toImageUrl(
        variantPath(variants.thumb) ?? variantPath(variants.md) ?? item.url ?? '',
    )
}

async function upload(files: File[] | FileList | null | undefined) {
    if (!files || !files.length) return

    const list = Array.from(files)
    multiFileWarn.value = list.length > 1
    if (list.length > 1) {
        error.value = null
    }

    const file = list[0]
    if (!file) return

    // Nudge when alt is empty — images need alt for a11y / SEO (Ahrefs hygiene).
    if (!altText.value.trim() && file.type.startsWith('image/')) {
        altNudge.value = true
    } else {
        altNudge.value = false
    }

    uploading.value = true
    if (!multiFileWarn.value) error.value = null

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
            error.value = null
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
    <div class="w-full min-w-0 space-y-3">
        <!-- Filled: preview + actions (no dashed border) -->
        <div v-if="preview" class="space-y-2">
            <div class="relative overflow-hidden rounded-lg border border-gray-200 bg-gray-100">
                <img
                    :src="preview"
                    :alt="selected?.alt_text ?? selected?.filename ?? ''"
                    class="max-h-48 w-full object-cover"
                >
                <div
                    v-if="uploading"
                    class="absolute inset-0 flex items-center justify-center bg-gray-900/50"
                >
                    <AppSpinner :size="28" />
                </div>
            </div>
            <p v-if="caption" class="truncate text-xs text-gray-500">{{ caption }}</p>
            <div class="flex flex-wrap gap-2">
                <AppFileInput
                    :accept="accept"
                    :disabled="uploading"
                    label="Replace"
                    variant="secondary"
                    @select="upload"
                />
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="uploading"
                    @click="openLibrary"
                >
                    Browse
                </button>
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md px-3 py-1.5 text-xs font-medium text-rose-600 hover:bg-rose-50 disabled:opacity-50"
                    :disabled="uploading"
                    @click="clear"
                >
                    Remove
                </button>
            </div>
        </div>

        <!-- Empty: full-width drop zone -->
        <div
            v-else
            class="relative flex w-full min-w-0 flex-col items-center gap-3 rounded-lg border-2 border-dashed px-4 py-6 text-center transition-colors"
            :class="dragOver
                ? 'border-brand-500 bg-brand-600/10'
                : 'border-gray-300 bg-gray-50'"
            @dragover.prevent="dragOver = true"
            @dragleave.prevent="dragOver = false"
            @drop.prevent="onDrop"
        >
            <div
                v-if="uploading"
                class="absolute inset-0 z-10 flex items-center justify-center rounded-lg bg-gray-900/40"
            >
                <AppSpinner :size="28" />
            </div>

            <div class="flex h-10 w-10 items-center justify-center rounded-full bg-gray-200 text-gray-500">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" />
                </svg>
            </div>

            <div class="min-w-0 space-y-1">
                <p class="text-sm font-medium text-gray-800">{{ label }}</p>
                <p class="text-xs text-gray-500">Drag &amp; drop or</p>
            </div>

            <div class="flex flex-wrap items-center justify-center gap-2">
                <AppFileInput
                    :accept="accept"
                    :disabled="uploading"
                    label="Upload"
                    variant="primary"
                    @select="upload"
                />
                <button
                    type="button"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50"
                    :disabled="uploading"
                    @click="openLibrary"
                >
                    Choose from library
                </button>
            </div>
        </div>

        <!-- Alt text: only relevant before / during upload (empty or replacing) -->
        <label v-if="!preview" class="block text-xs font-medium text-gray-500">
            Alt text
            <input
                v-model="altText"
                type="text"
                placeholder="Describe the image for accessibility…"
                class="mt-1 w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder:text-gray-400"
            >
        </label>
        <p v-if="!preview && altNudge" class="text-xs text-amber-600">
            Tip: add alt text — empty alt hurts accessibility and SEO.
        </p>
        <p v-if="multiFileWarn" class="text-xs text-amber-600">
            Only one file can be selected here — uploaded the first file.
        </p>
        <p v-if="error" class="text-xs text-rose-600">{{ error }}</p>

        <Teleport to="body">
            <div
                v-if="libraryOpen"
                class="fixed inset-0 z-[220] flex items-center justify-center bg-black/50 p-4"
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
                                class="group overflow-hidden rounded-lg border border-gray-200 bg-gray-50 text-left transition hover:border-brand-500"
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
