<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    caseStudies: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

const applyFilters = () => {
    router.get('/admin/case-studies', { search: search.value }, { preserveState: true, replace: true })
}

watch(search, applyFilters)

const deleteCaseStudy = (caseStudy) => {
    if (confirm(`Delete "${caseStudy.title}"?`)) {
        router.delete(`/admin/case-studies/${caseStudy.id}`)
    }
}
</script>

<template>
    <Head title="Case Studies" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Case Studies</h1>
        <Link href="/admin/case-studies/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            New Case Study
        </Link>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <input v-model="search" type="text" placeholder="Search case studies..." class="rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Title</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="cs in caseStudies.data" :key="cs.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ cs.title }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ cs.client_name || '-' }}</td>
                        <td class="px-6 py-4">
                            <span :class="[
                                'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                                cs.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                            ]">{{ cs.is_active ? 'Active' : 'Inactive' }}</span>
                        </td>
                        <td class="px-6 py-4 text-right text-sm">
                            <Link :href="`/admin/case-studies/${cs.id}/edit`" class="text-gray-600 hover:text-gray-900 mr-3">Edit</Link>
                            <button @click="deleteCaseStudy(cs)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!caseStudies.data?.length">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No case studies found.</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-if="caseStudies.links && caseStudies.links.length > 3" class="px-6 py-3 border-t flex justify-end">
            <nav class="flex space-x-1">
                <Link v-for="link in caseStudies.links" :key="link.label" :href="link.url || '#'" v-html="link.label" :class="['px-3 py-1 text-sm rounded', link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100', !link.url ? 'opacity-50 cursor-not-allowed' : '']" />
            </nav>
        </div>
    </div>
</template>
