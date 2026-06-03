<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    category: Object,
    parentCategories: Array,
})

const form = useForm({
    name: props.category.name,
    slug: props.category.slug,
    description: props.category.description || '',
    parent_id: props.category.parent_id || '',
    sort_order: props.category.sort_order ?? 0,
})

const submit = () => {
    form.put(`/admin/categories/${props.category.id}`)
}
</script>

<template>
    <Head title="Edit Category" />
    <div class="flex items-center mb-6">
        <Link href="/admin/categories" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Edit Category</h1>
    </div>
    <div class="bg-white rounded-lg shadow max-w-2xl">
        <form @submit.prevent="submit" class="p-6 space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Slug</label>
                <input v-model="form.slug" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea v-model="form.description" rows="3" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Parent Category</label>
                <select v-model="form.parent_id" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                    <option value="">None</option>
                    <option v-for="c in parentCategories" :key="c.id" :value="c.id">{{ c.name }}</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <Link href="/admin/categories" class="px-4 py-2 text-sm text-gray-700">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">Update</button>
            </div>
        </form>
    </div>
</template>
