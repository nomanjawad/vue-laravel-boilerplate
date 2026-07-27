<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

interface PaginatorLink {
    url: string | null
    label: string
    active: boolean
}

interface Paginator {
    current_page: number
    last_page: number
    per_page: number
    total: number
    links: PaginatorLink[]
}

interface Props {
    paginator: Paginator
}

// Accepts a Laravel paginator JSON ({ links: [...], meta: {...} } or the
// flat `{links, current_page, last_page, per_page, total}` shape).
defineProps<Props>()
</script>

<template>
    <nav v-if="paginator?.last_page > 1" class="mt-4 flex items-center justify-between gap-2 text-sm">
        <p class="text-gray-500">
            Page {{ paginator.current_page }} of {{ paginator.last_page }}
            ({{ paginator.total }} total)
        </p>
        <div class="flex gap-1">
            <template v-for="link in paginator.links" :key="link.label">
                <Link
                    v-if="link.url"
                    :href="link.url"
                    class="rounded border px-2 py-1"
                    :class="link.active ? 'border-indigo-500 bg-indigo-50 text-indigo-700' : 'border-gray-300 text-gray-700 hover:bg-gray-50'"
                    v-html="link.label"
                />
                <span v-else class="rounded border border-gray-200 px-2 py-1 text-gray-400" v-html="link.label" />
            </template>
        </div>
    </nav>
</template>
