<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref } from 'vue'

defineOptions({ layout: AdminLayout })

defineProps({
    redirects: Object,
    topNotFound: Array,
    filters: Object,
})

const newForm = useForm({
    from_path: '',
    to_path: '',
    status_code: 301,
    is_active: true,
})

const editingId = ref(null)
const editForm = useForm({
    from_path: '',
    to_path: '',
    status_code: 301,
    is_active: true,
})

const addRedirect = () => {
    newForm.post('/admin/redirects', {
        preserveScroll: true,
        onSuccess: () => newForm.reset('from_path', 'to_path'),
    })
}

const startEdit = (item) => {
    editingId.value = item.id
    editForm.from_path = item.from_path
    editForm.to_path = item.to_path
    editForm.status_code = item.status_code
    editForm.is_active = item.is_active
}

const saveEdit = (id) => {
    editForm.put(`/admin/redirects/${id}`, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null },
    })
}

const deleteRedirect = (id) => {
    if (confirm('Delete this redirect?')) {
        router.delete(`/admin/redirects/${id}`, { preserveScroll: true })
    }
}

// Quick action from the 404 report: prefill the form with the missed path.
const prefillFrom = (path) => {
    newForm.from_path = path
    window.scrollTo({ top: 0, behavior: 'smooth' })
}
</script>

<template>
    <Head title="Redirects" />
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Redirects</h1>

    <!-- Add new redirect -->
    <div class="bg-white rounded-lg shadow mb-6 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-1">Add Redirect</h2>
        <p class="text-sm text-gray-500 mb-4">Send visitors from an old URL to a new one (301 keeps search rankings).</p>
        <form @submit.prevent="addRedirect" class="flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">From path</label>
                <input v-model="newForm.from_path" type="text" required placeholder="/old-page" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                <p v-if="newForm.errors.from_path" class="text-xs text-red-600 mt-1">{{ newForm.errors.from_path }}</p>
            </div>
            <div class="flex-1 min-w-[180px]">
                <label class="block text-xs font-medium text-gray-500 mb-1">To path or URL</label>
                <input v-model="newForm.to_path" type="text" required placeholder="/new-page" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm" />
                <p v-if="newForm.errors.to_path" class="text-xs text-red-600 mt-1">{{ newForm.errors.to_path }}</p>
            </div>
            <div class="w-28">
                <label class="block text-xs font-medium text-gray-500 mb-1">Type</label>
                <select v-model.number="newForm.status_code" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option :value="301">301 permanent</option>
                    <option :value="302">302 temporary</option>
                </select>
            </div>
            <button type="submit" :disabled="newForm.processing" class="px-4 py-2 bg-gray-900 text-white text-sm rounded-md hover:bg-gray-800 disabled:opacity-50">
                Add
            </button>
        </form>
    </div>

    <!-- Top 404s -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b">
            <h2 class="text-lg font-semibold text-gray-900">Top 404s</h2>
            <p class="text-sm text-gray-500">Most-hit missing URLs — create redirects for the ones that matter.</p>
        </div>
        <div class="divide-y">
            <div v-for="log in topNotFound" :key="log.id" class="px-6 py-3 flex items-center justify-between">
                <div class="min-w-0">
                    <span class="text-sm font-medium text-gray-900">{{ log.path }}</span>
                    <span class="text-xs text-gray-400 ml-2">{{ log.hit_count }} hits</span>
                    <p v-if="log.referrer" class="text-xs text-gray-500 truncate">from {{ log.referrer }}</p>
                </div>
                <button @click="prefillFrom(log.path)" class="text-sm text-indigo-600 hover:text-indigo-800 shrink-0 ml-4">
                    Create redirect
                </button>
            </div>
            <div v-if="!topNotFound.length" class="px-6 py-4 text-sm text-gray-500">No 404s logged yet. 🎉</div>
        </div>
    </div>

    <!-- Redirect list -->
    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b"><h2 class="text-lg font-semibold text-gray-900">Active Redirects</h2></div>
        <div class="divide-y">
            <div v-for="item in redirects.data" :key="item.id" class="px-6 py-3 flex items-center justify-between">
                <template v-if="editingId === item.id">
                    <div class="flex gap-2 flex-1 items-center">
                        <input v-model="editForm.from_path" class="rounded-md border border-gray-300 px-2 py-1 text-sm flex-1" />
                        <input v-model="editForm.to_path" class="rounded-md border border-gray-300 px-2 py-1 text-sm flex-1" />
                        <select v-model.number="editForm.status_code" class="rounded-md border border-gray-300 px-2 py-1 text-sm">
                            <option :value="301">301</option>
                            <option :value="302">302</option>
                        </select>
                        <label class="text-sm text-gray-600 flex items-center gap-1">
                            <input v-model="editForm.is_active" type="checkbox" /> Active
                        </label>
                        <button @click="saveEdit(item.id)" class="text-sm text-green-600 hover:text-green-800">Save</button>
                        <button @click="editingId = null" class="text-sm text-gray-500 hover:text-gray-700">Cancel</button>
                    </div>
                </template>
                <template v-else>
                    <div class="min-w-0">
                        <span class="text-sm font-medium text-gray-900">{{ item.from_path }}</span>
                        <span class="text-sm text-gray-400 mx-1">→</span>
                        <span class="text-sm text-gray-700">{{ item.to_path }}</span>
                        <span class="text-xs text-gray-400 ml-2">{{ item.status_code }} · {{ item.hits }} hits</span>
                        <span v-if="!item.is_active" class="text-xs text-amber-600 ml-2">inactive</span>
                    </div>
                    <div class="flex space-x-2 shrink-0 ml-4">
                        <button @click="startEdit(item)" class="text-sm text-gray-600 hover:text-gray-900">Edit</button>
                        <button @click="deleteRedirect(item.id)" class="text-sm text-red-600 hover:text-red-900">Delete</button>
                    </div>
                </template>
            </div>
            <div v-if="!redirects.data.length" class="px-6 py-4 text-sm text-gray-500">No redirects yet.</div>
        </div>
    </div>
</template>
