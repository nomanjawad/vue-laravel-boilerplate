<script setup lang="ts">
/**
 * Public breadcrumb trail. Paired with BreadcrumbList JSON-LD from SeoService.
 * Hide on home (single crumb) — callers typically pass 2+ items.
 */
import { Link } from '@inertiajs/vue3'

export interface BreadcrumbItem {
    name: string
    url: string
}

interface Props {
    items?: BreadcrumbItem[]
}

withDefaults(defineProps<Props>(), {
    items: () => [],
})
</script>

<template>
    <nav
        v-if="items.length > 1"
        aria-label="Breadcrumb"
        class="border-b border-gray-100 bg-gray-50"
    >
        <ol class="mx-auto flex max-w-7xl flex-wrap items-center gap-1 px-4 py-3 text-sm text-gray-500 sm:px-6 lg:px-8">
            <li v-for="(item, i) in items" :key="i" class="inline-flex items-center gap-1">
                <span v-if="i > 0" aria-hidden="true" class="text-gray-300">/</span>
                <Link
                    v-if="i < items.length - 1"
                    :href="item.url"
                    class="hover:text-gray-800"
                >
                    {{ item.name }}
                </Link>
                <span v-else class="font-medium text-gray-800" aria-current="page">
                    {{ item.name }}
                </span>
            </li>
        </ol>
    </nav>
</template>
