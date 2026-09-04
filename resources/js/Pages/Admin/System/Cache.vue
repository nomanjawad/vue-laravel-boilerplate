<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppIcon from '@/Components/Atoms/AppIcon.vue'

defineOptions({ layout: AdminLayout })

interface CacheLayer {
    key: string
    label: string
    description: string
    last_cleared_at: string | null
}

interface Props {
    layers: CacheLayer[]
    last_cleared_all_at: string | null
}

const props = defineProps<Props>()

const busy = ref<string | null>(null)

function clearLayer(key: string) {
    if (key === 'all' && !confirm('Clear every managed cache layer? This does not wipe permissions or rate limits.')) {
        return
    }
    busy.value = key
    router.post(`/admin/system/cache/${key}`, {}, {
        preserveScroll: true,
        onFinish: () => { busy.value = null },
    })
}

function formatCleared(iso: string | null): string {
    if (!iso) return 'Never'
    try {
        return new Date(iso).toLocaleString()
    } catch {
        return iso
    }
}
</script>

<template>
    <Head title="Cache" />
    <div class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Cache</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Clear individual layers without wiping permission caches or rate-limiter counters.
                </p>
            </div>
            <button
                type="button"
                :disabled="busy !== null"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                @click="clearLayer('all')"
            >
                <AppIcon name="bolt" :size="16" />
                {{ busy === 'all' ? 'Clearing…' : 'Clear everything' }}
            </button>
        </div>

        <p v-if="last_cleared_all_at" class="text-xs text-gray-500">
            Last full clear: {{ formatCleared(last_cleared_all_at) }}
        </p>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div
                v-for="layer in layers"
                :key="layer.key"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="text-base font-semibold text-gray-900">{{ layer.label }}</h2>
                        <p class="mt-1 text-sm text-gray-500">{{ layer.description }}</p>
                        <p class="mt-3 text-xs text-gray-400">
                            Last cleared: {{ formatCleared(layer.last_cleared_at) }}
                        </p>
                    </div>
                    <button
                        type="button"
                        :disabled="busy !== null"
                        class="shrink-0 rounded-lg border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                        @click="clearLayer(layer.key)"
                    >
                        {{ busy === layer.key ? 'Clearing…' : 'Clear' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
