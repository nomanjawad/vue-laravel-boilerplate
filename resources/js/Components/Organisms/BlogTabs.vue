<script setup lang="ts">
// Posts/Categories/Tags share one sidebar entry (config/modules.php: blog.nav);
// this tab bar is how the three admin screens navigate between each other.
// Each tab is a real Inertia route (not client-only state), so Create/Edit
// sub-pages, deep links, and the browser back button all still work normally.
import { Link, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

const TABS = [
    { label: 'Posts', href: '/admin/posts' },
    { label: 'Categories', href: '/admin/categories' },
    { label: 'Tags', href: '/admin/tags' },
] as const

const page = usePage()
const currentUrl = computed(() => page.url)

function isActive(href: string): boolean {
    return currentUrl.value.startsWith(href)
}
</script>

<template>
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6" role="tablist" aria-label="Blog sections">
            <Link
                v-for="tab in TABS"
                :key="tab.href"
                :href="tab.href"
                role="tab"
                :aria-selected="isActive(tab.href)"
                class="border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                :class="isActive(tab.href)
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
            >
                {{ tab.label }}
            </Link>
        </nav>
    </div>
</template>
