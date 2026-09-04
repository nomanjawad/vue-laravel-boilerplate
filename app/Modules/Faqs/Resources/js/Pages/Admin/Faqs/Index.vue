<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

interface FaqListItem {
    id: number
    title: string
    page_slug?: string | null
    is_active?: boolean
}

interface PageOption {
    slug: string
    title: string
}

interface FaqFilters {
    search?: string | null
    page_slug?: string | null
}

interface Props {
    faqs: Illuminate.LengthAwarePaginator<number, FaqListItem>
    filters: FaqFilters
    pages: PageOption[]
}

const props = defineProps<Props>()

const search = ref(props.filters.search ?? '')
const pageSlug = ref(props.filters.page_slug ?? '')

function applyFilters() {
    router.get('/admin/faqs', {
        search: search.value || undefined,
        page_slug: pageSlug.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

let t: ReturnType<typeof setTimeout> | null = null
watch(search, () => {
    clearTimeout(t ?? undefined)
    t = setTimeout(applyFilters, 250)
})
watch(pageSlug, applyFilters)

function destroy(id: number) {
    if (!confirm('Delete this faq?')) return
    router.delete(`/admin/faqs/${id}`, { preserveScroll: true })
}

function pageLabel(slug: string | null | undefined): string {
    if (!slug) return 'Global'
    const match = props.pages.find((p) => p.slug === slug)
    return match?.title ?? slug
}
</script>

<template>
    <Head title="Faqs" />
    <div class="mb-6 flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-900">Faqs</h1>
        <Link
            href="/admin/faqs/create"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            New faq
        </Link>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input
            v-model="search"
            type="search"
            placeholder="Search questions…"
            class="w-full max-w-sm rounded border border-gray-300 px-3 py-2 text-sm"
        >
        <select
            v-model="pageSlug"
            class="rounded border border-gray-300 px-3 py-2 text-sm"
        >
            <option value="">All pages</option>
            <option value="__global__">Global only</option>
            <option v-for="p in pages" :key="p.slug" :value="p.slug">{{ p.title }}</option>
        </select>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Title</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Page</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Active</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="row in faqs.data" :key="row.id">
                    <td class="px-4 py-2 text-sm">
                        <Link :href="`/admin/faqs/${row.id}/edit`" class="text-indigo-600 hover:underline">
                            {{ row.title }}
                        </Link>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-600">
                        {{ pageLabel(row.page_slug) }}
                    </td>
                    <td class="px-4 py-2 text-sm">
                        <span v-if="row.is_active" class="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Active</span>
                        <span v-else class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">Inactive</span>
                    </td>
                    <td class="px-4 py-2 text-right text-sm">
                        <button type="button" class="text-rose-600 hover:underline" @click="destroy(row.id)">Delete</button>
                    </td>
                </tr>
                <tr v-if="!faqs.data.length">
                    <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">No faqs yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
