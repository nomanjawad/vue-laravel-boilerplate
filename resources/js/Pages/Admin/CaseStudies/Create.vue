<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const form = useForm({
    title: '',
    slug: '',
    client_name: '',
    excerpt: '',
    body: '',
    featured_image: '',
    is_active: true,
    sort_order: 0,
})

const submit = () => {
    form.post('/admin/case-studies')
}
</script>

<template>
    <Head title="Create Case Study" />
    <div class="flex items-center mb-6">
        <Link href="/admin/case-studies" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Create Case Study</h1>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input v-model="form.title" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Slug <span class="text-gray-400">(auto-generated if empty)</span></label>
                    <input v-model="form.slug" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Client Name</label>
                    <input v-model="form.client_name" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.client_name" class="mt-1 text-sm text-red-600">{{ form.errors.client_name }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Excerpt</label>
                    <textarea v-model="form.excerpt" rows="3" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.excerpt" class="mt-1 text-sm text-red-600">{{ form.errors.excerpt }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Body</label>
                    <textarea v-model="form.body" rows="15" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500 font-mono text-sm" />
                    <p v-if="form.errors.body" class="mt-1 text-sm text-red-600">{{ form.errors.body }}</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Featured Image URL</label>
                    <input v-model="form.featured_image" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.featured_image" class="mt-1 text-sm text-red-600">{{ form.errors.featured_image }}</p>
                    <div v-if="form.featured_image" class="mt-2">
                        <img :src="form.featured_image" alt="Preview" class="w-full h-32 object-cover rounded" />
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Sort Order</label>
                    <input v-model.number="form.sort_order" type="number" min="0" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="flex items-center">
                        <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500" />
                        <span class="ml-2 text-sm text-gray-700">Active</span>
                    </label>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <Link href="/admin/case-studies" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                    Create Case Study
                </button>
            </div>
        </div>
    </form>
</template>
