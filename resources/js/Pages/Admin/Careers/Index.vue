<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    careers: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const applyFilters = () => {
    router.get('/admin/careers', { search: search.value }, { preserveState: true, replace: true })
}

watch(search, applyFilters)

const deleteCareer = (career) => {
    if (confirm(`Delete "${career.title}"?`)) {
        router.delete(`/admin/careers/${career.id}`)
    }
}

const typeLabel = (type) => {
    const labels = {
        'full-time': 'Full Time',
        'part-time': 'Part Time',
        'contract': 'Contract',
        'remote': 'Remote',
    }
    return labels[type] || type
}
</script>

<template>
    <Head title="Careers" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Careers</h1>
        <Link href="/admin/careers/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            New Career
        </Link>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <input v-model="search" type="text" placeholder="Search careers..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Department</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="career in careers.data" :key="career.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ career.title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ career.department || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ career.location || '-' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ typeLabel(career.type) }}</td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                career.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ]">{{ career.is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <Link :href="`/admin/careers/${career.id}/edit`" class="text-gray-600 hover:text-gray-900 mr-3">Edit</Link>
                            <button @click="deleteCareer(career)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!careers.data?.length">
                        <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">No careers found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="careers.links && careers.links.length > 3" class="px-6 py-3 border-t flex justify-end">
            <nav class="flex space-x-1">
                <Link v-for="link in careers.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
            </nav>
        </div>
    </div>
</template>
