<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({ tags: Array })

const newForm = useForm({
    name: '',
})

const editingId = ref(null)
const editForm = useForm({
    name: '',
})

const addTag = () => {
    newForm.post('/admin/tags', {
        onSuccess: () => newForm.reset('name'),
    })
}

const startEdit = (tag) => {
    editingId.value = tag.id
    editForm.name = tag.name
}

const saveEdit = (id) => {
    editForm.put(`/admin/tags/${id}`, {
        onSuccess: () => { editingId.value = null },
    })
}

const deleteTag = (tag) => {
    if (confirm(`Delete "${tag.name}"?`)) {
        router.delete(`/admin/tags/${tag.id}`)
    }
}
</script>

<template>
    <Head title="Tags" />
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Tags</h1>

    <!-- Add new tag -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Tag</h2>
        <form @submit.prevent="addTag" class="flex gap-3 items-end">
            <div class="flex-1 min-w-[200px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Name</label>
                <input v-model="newForm.name" type="text" required placeholder="Tag name" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
            </div>
            <button type="submit" :disabled="newForm.processing" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800 disabled:opacity-50">
                Add
            </button>
        </form>
        <p v-if="newForm.errors.name" class="mt-1 text-sm text-red-600">{{ newForm.errors.name }}</p>
    </div>

    <!-- Tags list -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">All Tags</h2>
        </div>
        <div class="divide-y">
            <div v-for="tag in tags" :key="tag.id" class="px-6 py-3 flex items-center justify-between">
                <template v-if="editingId === tag.id">
                    <div class="flex gap-2 flex-1 items-center">
                        <input v-model="editForm.name" class="rounded-md border border-gray-300 px-2 py-1 text-sm flex-1" @keyup.enter="saveEdit(tag.id)" />
                        <button @click="saveEdit(tag.id)" class="text-sm text-green-600 hover:text-green-800">Save</button>
                        <button @click="editingId = null" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                    </div>
                </template>
                <template v-else>
                    <div class="flex items-center space-x-3">
                        <span class="text-sm font-medium text-gray-900">{{ tag.name }}</span>
                        <span class="text-xs text-gray-400">{{ tag.slug }}</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600">{{ tag.posts_count }} posts</span>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="startEdit(tag)" class="text-sm text-gray-600 hover:text-gray-900">Edit</button>
                        <button @click="deleteTag(tag)" class="text-sm text-red-600 hover:text-red-900">Delete</button>
                    </div>
                </template>
            </div>
            <div v-if="!tags.length" class="px-6 py-4 text-sm text-gray-500">No tags yet.</div>
        </div>
    </div>
</template>
