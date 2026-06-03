<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    pageMetas: Array,
})

const newForm = useForm({
    route_name: '',
    meta_title: '',
    meta_description: '',
    og_image: '',
})

const editingId = ref(null)
const editForm = useForm({
    meta_title: '',
    meta_description: '',
    og_image: '',
})

const addMeta = () => {
    newForm.post('/admin/page-metas', {
        onSuccess: () => newForm.reset(),
    })
}

const startEdit = (meta) => {
    editingId.value = meta.id
    editForm.meta_title = meta.meta_title || ''
    editForm.meta_description = meta.meta_description || ''
    editForm.og_image = meta.og_image || ''
}

const saveEdit = (id) => {
    editForm.put(`/admin/page-metas/${id}`, {
        onSuccess: () => { editingId.value = null },
    })
}

const deleteMeta = (id) => {
    if (confirm('Delete this page meta?')) {
        router.delete(`/admin/page-metas/${id}`)
    }
}
</script>

<template>
    <Head title="Page Metas" />
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Page SEO Meta</h1>

    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Page Meta</h2>
        <form @submit.prevent="addMeta" class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Route Name</label>
                    <input v-model="newForm.route_name" type="text" required placeholder="e.g. home, blog.index" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500 mb-1">Meta Title</label>
                    <input v-model="newForm.meta_title" type="text" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Meta Description</label>
                <textarea v-model="newForm.meta_description" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <button type="submit" :disabled="newForm.processing" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800 disabled:opacity-50">
                Add
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Route</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="meta in pageMetas" :key="meta.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ meta.route_name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <template v-if="editingId === meta.id">
                                <input v-model="editForm.meta_title" class="w-full rounded border border-gray-300 px-2 py-1 text-sm" />
                            </template>
                            <template v-else>{{ meta.meta_title || '-' }}</template>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                            <template v-if="editingId === meta.id">
                                <input v-model="editForm.meta_description" class="w-full rounded border border-gray-300 px-2 py-1 text-sm" />
                            </template>
                            <template v-else>{{ meta.meta_description || '-' }}</template>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <template v-if="editingId === meta.id">
                                <button @click="saveEdit(meta.id)" class="text-green-600 hover:text-green-800 mr-2">Save</button>
                                <button @click="editingId = null" class="text-gray-500 hover:text-gray-700">Cancel</button>
                            </template>
                            <template v-else>
                                <button @click="startEdit(meta)" class="text-gray-600 hover:text-gray-900 mr-3">Edit</button>
                                <button @click="deleteMeta(meta.id)" class="text-red-600 hover:text-red-900">Delete</button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
