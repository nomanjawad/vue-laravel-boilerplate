<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    career: Object,
})

const form = useForm({
    title: props.career.title,
    slug: props.career.slug,
    department: props.career.department || '',
    location: props.career.location || '',
    type: props.career.type,
    description: props.career.description,
    requirements: props.career.requirements || '',
    is_active: props.career.is_active,
    sort_order: props.career.sort_order || 0,
})

const submit = () => {
    form.put(`/admin/careers/${props.career.id}`)
}
</script>

<template>
    <Head title="Edit Career" />
    <div class="flex items-center mb-6">
        <Link href="/admin/careers" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Edit Career</h1>
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
                    <label class="block text-sm font-medium text-gray-700">Slug</label>
                    <input v-model="form.slug" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.slug" class="mt-1 text-sm text-red-600">{{ form.errors.slug }}</p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Department</label>
                        <input v-model="form.department" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.department" class="mt-1 text-sm text-red-600">{{ form.errors.department }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Location</label>
                        <input v-model="form.location" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.location" class="mt-1 text-sm text-red-600">{{ form.errors.location }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea v-model="form.description" rows="10" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.description" class="mt-1 text-sm text-red-600">{{ form.errors.description }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Requirements</label>
                    <textarea v-model="form.requirements" rows="6" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.requirements" class="mt-1 text-sm text-red-600">{{ form.errors.requirements }}</p>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <select v-model="form.type" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="full-time">Full Time</option>
                        <option value="part-time">Part Time</option>
                        <option value="contract">Contract</option>
                        <option value="remote">Remote</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-1 text-sm text-red-600">{{ form.errors.type }}</p>
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
                <Link href="/admin/careers" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                    Update Career
                </button>
            </div>
        </div>
    </form>
</template>
