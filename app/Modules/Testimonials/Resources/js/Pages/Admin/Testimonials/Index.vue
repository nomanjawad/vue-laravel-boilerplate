<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'
import { route } from 'ziggy-js'

defineOptions({ layout: AdminLayout })

interface TestimonialListItem {
    id: number
    title: string
    is_active?: boolean
}

interface TestimonialFilters {
    search?: string
}

interface Props {
    testimonials: Illuminate.LengthAwarePaginator<number, TestimonialListItem>
    filters: TestimonialFilters
}

const props = defineProps<Props>()

const search = ref(props.filters.search ?? '')

let t: ReturnType<typeof setTimeout> | null = null
watch(search, (v) => {
    clearTimeout(t ?? undefined)
    t = setTimeout(() => {
        router.get(route('admin.testimonials.index'), { search: v || undefined }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        })
    }, 250)
})

function destroy(id: number) {
    if (!confirm('Delete this testimonial?')) return
    router.delete(route('admin.testimonials.destroy', id), { preserveScroll: true })
}
</script>

<template>
    <Head title="Testimonials" />
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Testimonials</h1>
        <Link
            :href="route('admin.testimonials.create')"
            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
        >
            New testimonial
        </Link>
    </div>

    <input
        v-model="search"
        type="search"
        placeholder="Search…"
        class="mb-4 w-full rounded border border-gray-300 px-3 py-2 text-sm"
    >

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Title</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Active</th>
                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500" />
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                <tr v-for="row in testimonials.data" :key="row.id">
                    <td class="px-4 py-2 text-sm">
                        <Link :href="route('admin.testimonials.edit', row.id)" class="text-indigo-600 hover:underline">
                            {{ row.title }}
                        </Link>
                    </td>
                    <td class="px-4 py-2 text-sm">
                        <span v-if="row.is_active" class="rounded bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700">Active</span>
                        <span v-else class="rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-700">Inactive</span>
                    </td>
                    <td class="px-4 py-2 text-right text-sm">
                        <button type="button" class="text-rose-600 hover:underline" @click="destroy(row.id)">Delete</button>
                    </td>
                </tr>
                <tr v-if="!testimonials.data.length">
                    <td colspan="3" class="px-4 py-6 text-center text-sm text-gray-500">No testimonials yet.</td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
