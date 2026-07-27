<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

interface ActivityCauser {
    id: number
    name: string
    email: string
}

interface ActivityRow {
    id: number
    log_name: string | null
    description: string
    subject_type: string | null
    subject_id: number | null
    causer: ActivityCauser | null
    properties: Record<string, unknown> | null
    created_at: string
}

interface Filters {
    log_name?: string
    causer_id?: number
    search?: string
}

const props = defineProps<{
    activities: Illuminate.LengthAwarePaginator<number, ActivityRow>
    filters: Filters
}>()

const search = ref<string>(props.filters.search ?? '')
const logName = ref<string>(props.filters.log_name ?? '')

let t: ReturnType<typeof setTimeout> | null = null
watch([search, logName], () => {
    if (t) clearTimeout(t)
    t = setTimeout(() => {
        router.get(
            '/admin/audit-log',
            {
                search: search.value || undefined,
                log_name: logName.value || undefined,
            },
            { preserveState: true, preserveScroll: true, replace: true },
        )
    }, 250)
})

function formatWhen(iso: string): string {
    return new Date(iso).toLocaleString()
}
</script>

<template>
    <Head title="Audit log" />
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Audit log</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Who did what, and when. Content CRUD + login events.
                </p>
            </div>
        </div>

        <div class="mb-4 flex flex-wrap gap-2">
            <input
                v-model="search"
                type="search"
                placeholder="Search description or subject…"
                class="rounded border border-gray-300 px-3 py-2 text-sm"
            >
            <select
                v-model="logName"
                class="rounded border border-gray-300 px-3 py-2 text-sm"
            >
                <option value="">All streams</option>
                <option value="default">Content</option>
                <option value="auth">Auth</option>
            </select>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">When</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Who</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Stream</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Description</th>
                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500">Subject</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr v-for="row in activities.data" :key="row.id">
                        <td class="whitespace-nowrap px-4 py-2 text-gray-600">{{ formatWhen(row.created_at) }}</td>
                        <td class="px-4 py-2 text-gray-900">
                            <span v-if="row.causer">{{ row.causer.name }}</span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                        <td class="px-4 py-2">
                            <span
                                class="rounded px-2 py-0.5 text-xs"
                                :class="row.log_name === 'auth' ? 'bg-amber-100 text-amber-700' : 'bg-indigo-100 text-indigo-700'"
                            >
                                {{ row.log_name || 'default' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-gray-800">{{ row.description }}</td>
                        <td class="px-4 py-2 text-gray-500">
                            <span v-if="row.subject_type">
                                {{ row.subject_type.split('\\').pop() }}
                                <span v-if="row.subject_id">#{{ row.subject_id }}</span>
                            </span>
                            <span v-else class="text-gray-400">—</span>
                        </td>
                    </tr>
                    <tr v-if="!activities.data.length">
                        <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
                            No activity yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
