<script setup lang="ts">
import { ref, computed, watch, nextTick, onMounted, onBeforeUnmount } from 'vue'
import { router } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

interface Props {
    open?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    open: false,
})

const emit = defineEmits<{
    (e: 'close'): void
}>()

interface SearchResponse {
    groups?: App.Data.SearchGroupData[]
}

interface FlatResult extends App.Data.SearchResultData {
    groupLabel: string
}

const query = ref('')
const groups = ref<App.Data.SearchGroupData[]>([])
const loading = ref(false)
const activeIndex = ref(-1)
const inputRef = ref<HTMLInputElement | null>(null)

let debounceTimer: ReturnType<typeof setTimeout> | null = null

const flatResults = ref<FlatResult[]>([])

// Each group's results paired with their global flat-list index so template
// highlighting stays correct even when two results share the same href.
const groupsWithIndex = computed(() => {
    let cursor = 0
    return groups.value.map((group) => {
        const items = (group.results ?? []).map((result) => ({
            result,
            flatIndex: cursor++,
        }))
        return { module: group.module, label: group.label, items }
    })
})

function rebuildFlatResults() {
    const flat: FlatResult[] = []
    for (const group of groups.value) {
        for (const result of group.results ?? []) {
            flat.push({ ...result, groupLabel: group.label })
        }
    }
    flatResults.value = flat
    activeIndex.value = flat.length ? 0 : -1
}

async function runSearch() {
    const q = query.value.trim()
    if (q.length < 2) {
        groups.value = []
        flatResults.value = []
        activeIndex.value = -1
        return
    }

    loading.value = true
    try {
        const url = `${route('admin.search.index')}?${new URLSearchParams({ q })}`
        const res = await fetch(url, {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
        if (!res.ok) return
        const json = (await res.json()) as SearchResponse
        groups.value = json.groups ?? []
        rebuildFlatResults()
    } catch {
        groups.value = []
        flatResults.value = []
    } finally {
        loading.value = false
    }
}

watch(() => props.open, async (isOpen) => {
    if (!isOpen) {
        query.value = ''
        groups.value = []
        flatResults.value = []
        activeIndex.value = -1
        return
    }
    await nextTick()
    inputRef.value?.focus()
})

watch(query, () => {
    if (debounceTimer !== null) clearTimeout(debounceTimer)
    debounceTimer = setTimeout(runSearch, 200)
})

function visit(result: FlatResult | App.Data.SearchResultData | undefined) {
    if (!result?.href) return
    emit('close')
    router.visit(result.href)
}

function onKeydown(e: KeyboardEvent) {
    if (e.key === 'Escape') {
        e.preventDefault()
        emit('close')
        return
    }

    if (!flatResults.value.length) return

    if (e.key === 'ArrowDown') {
        e.preventDefault()
        activeIndex.value = (activeIndex.value + 1) % flatResults.value.length
    } else if (e.key === 'ArrowUp') {
        e.preventDefault()
        activeIndex.value = (activeIndex.value - 1 + flatResults.value.length) % flatResults.value.length
    } else if (e.key === 'Enter' && activeIndex.value >= 0) {
        e.preventDefault()
        visit(flatResults.value[activeIndex.value])
    }
}

onMounted(() => document.addEventListener('keydown', onKeydown))
onBeforeUnmount(() => {
    document.removeEventListener('keydown', onKeydown)
    if (debounceTimer !== null) clearTimeout(debounceTimer)
})
</script>

<template>
    <div
        v-if="open"
        class="fixed inset-0 z-[70] flex items-start justify-center bg-gray-900/50 p-4 pt-[12vh]"
        @click.self="emit('close')"
    >
        <div class="w-full max-w-xl overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl">
            <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-3">
                <svg class="h-5 w-5 shrink-0 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 1 0 0-14 7 7 0 0 0 0 14z" />
                </svg>
                <input
                    ref="inputRef"
                    v-model="query"
                    type="search"
                    data-admin-search
                    placeholder="Search posts, products, orders…"
                    class="w-full border-0 bg-transparent text-sm text-gray-900 placeholder:text-gray-400 focus:outline-none focus:ring-0"
                >
                <kbd class="hidden rounded border border-gray-200 bg-gray-50 px-1.5 py-0.5 text-[10px] text-gray-500 sm:inline">esc</kbd>
            </div>

            <div class="max-h-[50vh] overflow-y-auto p-2">
                <p v-if="loading" class="px-3 py-6 text-center text-sm text-gray-500">Searching…</p>

                <p v-else-if="query.trim().length < 2" class="px-3 py-6 text-center text-sm text-gray-500">
                    Type at least 2 characters to search enabled modules.
                </p>

                <p v-else-if="!groups.length" class="px-3 py-6 text-center text-sm text-gray-500">
                    No results for “{{ query.trim() }}”.
                </p>

                <div v-else class="space-y-3">
                    <section v-for="group in groupsWithIndex" :key="group.module + group.label">
                        <h3 class="px-3 py-1 text-xs font-semibold uppercase tracking-wide text-gray-500">
                            {{ group.label }}
                        </h3>
                        <ul>
                            <li
                                v-for="{ result, flatIndex } in group.items"
                                :key="`${group.label}-${flatIndex}`"
                            >
                                <button
                                    type="button"
                                    class="flex w-full items-start gap-2 rounded-lg px-3 py-2 text-left text-sm transition-colors"
                                    :class="activeIndex === flatIndex ? 'bg-indigo-50 text-indigo-900' : 'text-gray-800 hover:bg-gray-50'"
                                    @click="visit(result)"
                                    @mouseenter="activeIndex = flatIndex"
                                >
                                    <span class="font-medium">{{ result.title }}</span>
                                    <span v-if="result.subtitle" class="text-gray-500">· {{ result.subtitle }}</span>
                                </button>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
