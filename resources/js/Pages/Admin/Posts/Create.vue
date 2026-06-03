<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    categories: Array,
    tags: Array,
})

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    category_id: '',
    status: 'draft',
    featured_image: '',
    meta_title: '',
    meta_description: '',
    tags: [],
})

const submit = () => {
    form.post('/admin/posts')
}
</script>

<template>
    <Head title="Create Post" />
    <div class="flex items-center mb-6">
        <Link href="/admin/posts" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Create Post</h1>
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
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Excerpt</label>
                    <textarea v-model="form.excerpt" rows="2" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Body</label>
                    <textarea v-model="form.body" rows="15" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500 font-mono text-sm" />
                    <p v-if="form.errors.body" class="mt-1 text-sm text-red-600">{{ form.errors.body }}</p>
                </div>
            </div>

            <!-- SEO -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">SEO</h3>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                    <input v-model="form.meta_title" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                    <textarea v-model="form.meta_description" rows="2" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select v-model="form.status" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Category</label>
                    <select v-model="form.category_id" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="">None</option>
                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">{{ cat.name }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Featured Image URL</label>
                    <input v-model="form.featured_image" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <div class="space-y-1 max-h-40 overflow-y-auto">
                        <label v-for="tag in tags" :key="tag.id" class="flex items-center">
                            <input v-model="form.tags" :value="tag.id" type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500" />
                            <span class="ml-2 text-sm text-gray-700">{{ tag.name }}</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="flex justify-end space-x-3">
                <Link href="/admin/posts" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                    Create Post
                </button>
            </div>
        </div>
    </form>
</template>
