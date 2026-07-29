<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

interface CustomCodeRow {
    id: number
    name: string
    placement: 'head' | 'body_start' | 'body_end'
    is_active: boolean
    sort_order: number
}

interface Props {
    codes: CustomCodeRow[]
}

defineProps<Props>()

const PLACEMENT_LABEL: Record<CustomCodeRow['placement'], string> = {
    head: 'Head',
    body_start: 'Body start',
    body_end: 'Body end',
}

function toggleActive(code: CustomCodeRow) {
    router.patch(`/admin/custom-code/${code.id}/toggle`, {}, { preserveScroll: true })
}

function deleteCode(code: CustomCodeRow) {
    if (confirm(`Delete "${code.name}"?`)) {
        router.delete(`/admin/custom-code/${code.id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Custom Code" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Custom Code</h1>
        <Link href="/admin/custom-code/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            Add Snippet
        </Link>
    </div>

    <p class="mb-4 text-sm text-gray-500">
        Snippets are injected verbatim into every public page — not the admin panel — at the
        chosen placement. There's no sandboxing, so only add code you trust.
    </p>

    <div class="bg-white rounded-lg shadow">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Placement</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Active</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <tr v-for="code in codes" :key="code.id">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ code.name }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ PLACEMENT_LABEL[code.placement] }}</td>
                        <td class="px-6 py-4 text-sm">
                            <button
                                type="button"
                                class="rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="code.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'"
                                @click="toggleActive(code)"
                            >
                                {{ code.is_active ? 'Active' : 'Inactive' }}
                            </button>
                        </td>
                        <td class="px-6 py-4 text-right text-sm space-x-3">
                            <Link :href="`/admin/custom-code/${code.id}/edit`" class="text-gray-600 hover:text-gray-900">Edit</Link>
                            <button type="button" class="text-rose-600 hover:text-rose-800" @click="deleteCode(code)">Delete</button>
                        </td>
                    </tr>
                    <tr v-if="!codes.length">
                        <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No snippets yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
