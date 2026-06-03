<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const form = useForm({
    name: '',
    position: '',
    bio: '',
    photo: '',
    email: '',
    social_links: {
        facebook: '',
        twitter: '',
        linkedin: '',
    },
    sort_order: 0,
    is_active: true,
})

const submit = () => {
    form.post('/admin/teams')
}
</script>

<template>
    <Head title="Add Team Member" />
    <div class="flex items-center mb-6">
        <Link href="/admin/teams" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Add Team Member</h1>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Position</label>
                        <input v-model="form.position" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.position" class="mt-1 text-sm text-red-600">{{ form.errors.position }}</p>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Email</label>
                    <input v-model="form.email" type="email" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bio</label>
                    <textarea v-model="form.bio" rows="6" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.bio" class="mt-1 text-sm text-red-600">{{ form.errors.bio }}</p>
                </div>
            </div>

            <!-- Social Links -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">Social Links</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Facebook URL</label>
                    <input v-model="form.social_links.facebook" type="url" placeholder="https://facebook.com/..." class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Twitter URL</label>
                    <input v-model="form.social_links.twitter" type="url" placeholder="https://twitter.com/..." class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">LinkedIn URL</label>
                    <input v-model="form.social_links.linkedin" type="url" placeholder="https://linkedin.com/in/..." class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Photo URL</label>
                    <input v-model="form.photo" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.photo" class="mt-1 text-sm text-red-600">{{ form.errors.photo }}</p>
                    <div v-if="form.photo" class="mt-2">
                        <img :src="form.photo" alt="Preview" class="w-full h-40 object-cover rounded" />
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
                <Link href="/admin/teams" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                    Add Member
                </button>
            </div>
        </div>
    </form>
</template>
