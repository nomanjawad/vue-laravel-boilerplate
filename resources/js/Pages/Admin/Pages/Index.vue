<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

interface PageRow {
    slug: string
    title: string
    status: string
    updated_at?: string | null
}

interface Props {
    pages: PageRow[]
}

defineProps<Props>()

const deletePage = (page: PageRow) => {
    if (page.slug === 'home') {
        alert('The home page cannot be deleted.')
        return
    }
    if (confirm(`Delete "${page.title}"?`)) {
        router.delete(`/admin/pages/${page.slug}`)
    }
}

function formatDate(iso?: string | null): string {
    if (!iso) return '—'
    try {
        return new Date(iso).toLocaleString()
    } catch {
        return iso
    }
}
</script>

<template>
    <Head title="Pages" />
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Pages</h1>
        <Link
            href="/admin/pages/create"
            class="inline-flex items-center rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
        >
            New Page
        </Link>
    </div>

    <div class="overflow-hidden rounded-lg bg-white shadow">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Title</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Slug</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Updated</th>
                    <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                <tr v-for="page in pages" :key="page.slug">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ page.title }}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">/{{ page.slug === 'home' ? '' : page.slug }}</td>
                    <td class="px-6 py-4">
                        <span
                            :class="[
                                'inline-flex items-center rounded px-2 py-0.5 text-xs font-medium',
                                page.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800',
                            ]"
                        >
                            {{ page.status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500">{{ formatDate(page.updated_at) }}</td>
                    <td class="px-6 py-4 text-right text-sm">
                        <Link :href="`/admin/pages/${page.slug}/edit`" class="mr-3 text-gray-600 hover:text-gray-900">Edit</Link>
                        <button
                            v-if="page.slug !== 'home'"
                            type="button"
                            class="text-red-600 hover:text-red-900"
                            @click="deletePage(page)"
                        >
                            Delete
                        </button>
                    </td>
                </tr>
                <tr v-if="!pages.length">
                    <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">No pages yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
