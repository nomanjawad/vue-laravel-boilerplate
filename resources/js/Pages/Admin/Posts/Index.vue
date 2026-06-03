<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    posts: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')

const applyFilters = () => {
    router.get('/admin/posts', { search: search.value, status: status.value }, { preserveState: true, replace: true })
}

watch([search, status], applyFilters)

const deletePost = (post) => {
    if (confirm(`Delete "${post.title}"?`)) {
        router.delete(`/admin/posts/${post.id}`)
    }
}
</script>

<template>
    <Head title="Posts" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Posts</h1>
        <Link href="/admin/posts/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            New Post
        </Link>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b flex flex-wrap gap-3">
            <input v-model="search" type="text" placeholder="Search posts..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
            <select v-model="status" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                <option value="">All Status</option>
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Author</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Category</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="post in posts.data" :key="post.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ post.title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ post.user?.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ post.category?.name || '-' }}</td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                post.status === 'published' ? 'bg-green-100 text-green-800' :
                                post.status === 'draft' ? 'bg-yellow-100 text-yellow-800' :
                                'bg-gray-100 text-gray-800'
                            ]">{{ post.status }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <Link :href="`/admin/posts/${post.id}/edit`" class="text-gray-600 hover:text-gray-900 mr-3">Edit</Link>
                            <button @click="deletePost(post)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="posts.links && posts.links.length > 3" class="px-6 py-3 border-t flex justify-end">
            <nav class="flex space-x-1">
                <Link v-for="link in posts.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
            </nav>
        </div>
    </div>
</template>
