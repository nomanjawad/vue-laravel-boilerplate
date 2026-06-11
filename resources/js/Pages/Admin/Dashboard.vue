<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

defineProps({
    stats: {
        type: Object,
        default: () => ({})
    },
    topNotFound: {
        type: Array,
        default: () => []
    }
})

const clearing = ref(false)

function clearCache() {
    clearing.value = true
    router.post('/admin/cache/clear', {}, {
        preserveScroll: true,
        onFinish: () => (clearing.value = false),
    })
}
</script>

<template>
    <Head title="Dashboard" />
    <div>
        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <button
                type="button"
                :disabled="clearing"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                @click="clearCache"
            >
                {{ clearing ? 'Clearing…' : 'Clear Cache' }}
            </button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Users</h3>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.users || 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Posts</h3>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.posts || 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Products</h3>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.products || 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-sm font-medium text-gray-500">Total Orders</h3>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ stats.orders || 0 }}</p>
            </div>
        </div>

        <!-- 404 monitoring: most-hit missing URLs feed the redirect manager -->
        <div class="bg-white rounded-lg shadow mt-6">
            <div class="px-6 py-4 border-b flex items-center justify-between">
                <h2 class="text-lg font-semibold text-gray-900">Top 404s</h2>
                <Link href="/admin/redirects" class="text-sm text-indigo-600 hover:text-indigo-800">Manage redirects →</Link>
            </div>
            <div class="divide-y">
                <div v-for="log in topNotFound" :key="log.id" class="px-6 py-3 flex items-center justify-between">
                    <span class="text-sm text-gray-900 truncate">{{ log.path }}</span>
                    <span class="text-xs text-gray-400 shrink-0 ml-4">{{ log.hit_count }} hits</span>
                </div>
                <div v-if="!topNotFound.length" class="px-6 py-4 text-sm text-gray-500">No 404s logged. 🎉</div>
            </div>
        </div>
    </div>
</template>
