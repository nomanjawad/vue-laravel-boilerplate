<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    headerMenus: Array,
    footerMenus: Array,
})

const newForm = useForm({
    location: 'header',
    title: '',
    url: '',
    sort_order: 0,
    is_active: true,
})

const editingId = ref(null)
const editForm = useForm({
    title: '',
    url: '',
    sort_order: 0,
    is_active: true,
})

const addMenu = () => {
    newForm.post('/admin/menus', {
        onSuccess: () => newForm.reset('title', 'url'),
    })
}

const startEdit = (item) => {
    editingId.value = item.id
    editForm.title = item.title
    editForm.url = item.url
    editForm.sort_order = item.sort_order
    editForm.is_active = item.is_active
}

const saveEdit = (id) => {
    editForm.put(`/admin/menus/${id}`, {
        onSuccess: () => { editingId.value = null },
    })
}

const deleteMenu = (id) => {
    if (confirm('Delete this menu item?')) {
        router.delete(`/admin/menus/${id}`)
    }
}
</script>

<template>
    <Head title="Menus" />
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Menu Manager</h1>

    <!-- Add new menu item -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Add Menu Item</h2>
        <form @submit.prevent="addMenu" class="flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Location</label>
                <select v-model="newForm.location" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="header">Header</option>
                    <option value="footer">Footer</option>
                </select>
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">Title</label>
                <input v-model="newForm.title" type="text" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <div class="flex-1 min-w-[150px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">URL</label>
                <input v-model="newForm.url" type="text" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <div class="w-20">
                <label class="block text-xs font-medium text-gray-500 mb-1">Order</label>
                <input v-model.number="newForm.sort_order" type="number" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <button type="submit" :disabled="newForm.processing" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800 disabled:opacity-50">
                Add
            </button>
        </form>
    </div>

    <!-- Header menus -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold text-gray-900">Header Menu</h2></div>
        <div class="divide-y">
            <div v-for="item in headerMenus" :key="item.id" class="px-6 py-3 flex items-center justify-between">
                <template v-if="editingId === item.id">
                    <div class="flex gap-2 flex-1 items-center">
                        <input v-model="editForm.title" class="rounded-md border border-gray-300 px-2 py-1 text-sm flex-1" />
                        <input v-model="editForm.url" class="rounded-md border border-gray-300 px-2 py-1 text-sm flex-1" />
                        <input v-model.number="editForm.sort_order" type="number" class="rounded-md border border-gray-300 px-2 py-1 text-sm w-16" />
                        <button @click="saveEdit(item.id)" class="text-sm text-green-600 hover:text-green-800">Save</button>
                        <button @click="editingId = null" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                    </div>
                </template>
                <template v-else>
                    <div>
                        <span class="text-sm font-medium text-gray-900">{{ item.title }}</span>
                        <span class="text-sm text-gray-500 ml-2">{{ item.url }}</span>
                        <span class="text-xs text-gray-400 ml-2">#{{ item.sort_order }}</span>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="startEdit(item)" class="text-sm text-gray-600 hover:text-gray-900">Edit</button>
                        <button @click="deleteMenu(item.id)" class="text-sm text-red-600 hover:text-red-900">Delete</button>
                    </div>
                </template>
            </div>
            <div v-if="!headerMenus.length" class="px-6 py-4 text-sm text-gray-500">No header menu items.</div>
        </div>
    </div>

    <!-- Footer menus -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold text-gray-900">Footer Menu</h2></div>
        <div class="divide-y">
            <div v-for="item in footerMenus" :key="item.id" class="px-6 py-3 flex items-center justify-between">
                <template v-if="editingId === item.id">
                    <div class="flex gap-2 flex-1 items-center">
                        <input v-model="editForm.title" class="rounded-md border border-gray-300 px-2 py-1 text-sm flex-1" />
                        <input v-model="editForm.url" class="rounded-md border border-gray-300 px-2 py-1 text-sm flex-1" />
                        <input v-model.number="editForm.sort_order" type="number" class="rounded-md border border-gray-300 px-2 py-1 text-sm w-16" />
                        <button @click="saveEdit(item.id)" class="text-sm text-green-600 hover:text-green-800">Save</button>
                        <button @click="editingId = null" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                    </div>
                </template>
                <template v-else>
                    <div>
                        <span class="text-sm font-medium text-gray-900">{{ item.title }}</span>
                        <span class="text-sm text-gray-500 ml-2">{{ item.url }}</span>
                        <span class="text-xs text-gray-400 ml-2">#{{ item.sort_order }}</span>
                    </div>
                    <div class="flex space-x-2">
                        <button @click="startEdit(item)" class="text-sm text-gray-600 hover:text-gray-900">Edit</button>
                        <button @click="deleteMenu(item.id)" class="text-sm text-red-600 hover:text-red-900">Delete</button>
                    </div>
                </template>
            </div>
            <div v-if="!footerMenus.length" class="px-6 py-4 text-sm text-gray-500">No footer menu items.</div>
        </div>
    </div>
</template>
