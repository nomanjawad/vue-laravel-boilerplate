<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({ categories: Array })

const deleteCategory = (cat) => {
    if (confirm(`Delete "${cat.name}"?`)) {
        router.delete(`/admin/categories/${cat.id}`)
    }
}
</script>

<template>
    <Head title="Categories" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Categories</h1>
        <Link href="/admin/categories/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">Add Category</Link>
    </div>
    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Slug</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Posts</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="cat in categories" :key="cat.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ cat.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ cat.slug }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ cat.posts_count }}</td>
                        <td class="px-6 py-4 text-right text-sm">
                            <Link :href="`/admin/categories/${cat.id}/edit`" class="text-gray-600 hover:text-gray-900 mr-3">Edit</Link>
                            <button @click="deleteCategory(cat)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
