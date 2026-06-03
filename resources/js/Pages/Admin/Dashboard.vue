<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

defineProps({
    stats: {
        type: Object,
        default: () => ({})
    }
})
</script>

<template>
    <Head title="Dashboard" />
    <div>
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Dashboard</h1>
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
    </div>
</template>
