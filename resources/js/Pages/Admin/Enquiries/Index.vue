<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import Can from '@/Components/Shared/Can.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({ layout: AdminLayout })

interface EnquiryRow {
    id: number
    name: string
    email: string
    phone: string | null
    subject: string
    message: string
    ip: string | null
    read_at: string | null
    created_at: string
}

interface EnquiryFilters {
    search?: string | null
    status?: string | null
    id?: number | null
}

interface Props {
    enquiries: Illuminate.LengthAwarePaginator<number, EnquiryRow>
    selected: EnquiryRow | null
    filters: EnquiryFilters
}

const props = defineProps<Props>()
const { confirm } = useConfirm()

const search = ref(props.filters?.search || '')
const status = ref(props.filters?.status || '')
const selectedIds = ref<number[]>([])

let timer: ReturnType<typeof setTimeout> | null = null

function currentListPage(): number {
    const raw = props.enquiries as { current_page?: number; meta?: { current_page?: number } }
    return raw.current_page ?? raw.meta?.current_page ?? 1
}

function applyFilters(extra: Record<string, string | number | undefined> = {}) {
    const params: Record<string, string | number> = {}
    if (search.value) params.search = search.value
    if (status.value) params.status = status.value
    for (const [k, v] of Object.entries(extra)) {
        if (v !== undefined && v !== '') params[k] = v
    }

    router.get('/admin/enquiries', params, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    })
}

watch(search, () => {
    if (timer) clearTimeout(timer)
    timer = setTimeout(() => applyFilters({
        ...(props.selected ? { id: props.selected.id } : {}),
        page: currentListPage(),
    }), 300)
})

watch(status, () => {
    applyFilters()
})

const allOnPageSelected = computed(() => {
    const ids = props.enquiries.data.map((e) => e.id)
    return ids.length > 0 && ids.every((id) => selectedIds.value.includes(id))
})

function toggleAll() {
    const ids = props.enquiries.data.map((e) => e.id)
    if (allOnPageSelected.value) {
        selectedIds.value = selectedIds.value.filter((id) => !ids.includes(id))
    } else {
        selectedIds.value = [...new Set([...selectedIds.value, ...ids])]
    }
}

function toggleOne(id: number) {
    if (selectedIds.value.includes(id)) {
        selectedIds.value = selectedIds.value.filter((x) => x !== id)
    } else {
        selectedIds.value = [...selectedIds.value, id]
    }
}

function openEnquiry(id: number) {
    applyFilters({ id, page: currentListPage() })
}

function closeDetail() {
    applyFilters()
}

function markUnread(id: number) {
    router.post(`/admin/enquiries/${id}/unread`, {}, { preserveScroll: true })
}

function markRead(id: number) {
    router.post(`/admin/enquiries/${id}/read`, {}, { preserveScroll: true })
}

async function bulkMarkRead() {
    if (!selectedIds.value.length) return
    router.post('/admin/enquiries/bulk-read', { ids: selectedIds.value }, {
        preserveScroll: true,
        onSuccess: () => { selectedIds.value = [] },
    })
}

async function destroy(id: number) {
    if (!await confirm({ title: 'Delete this enquiry?', confirmTone: 'danger' })) return
    router.delete(`/admin/enquiries/${id}`, { preserveScroll: true })
}

function mailtoHref(row: EnquiryRow): string {
    const subject = encodeURIComponent(`Re: ${row.subject}`)
    return `mailto:${row.email}?subject=${subject}`
}

function formatDate(iso: string): string {
    try {
        return new Date(iso).toLocaleString()
    } catch {
        return iso
    }
}
</script>

<template>
    <Head title="Enquiries" />

    <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold text-gray-900">Enquiries</h1>
        <Can permission="enquiries.update">
            <button
                v-if="selectedIds.length"
                type="button"
                class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                @click="bulkMarkRead"
            >
                Mark {{ selectedIds.length }} read
            </button>
        </Can>
    </div>

    <div class="mb-4 flex flex-wrap gap-3">
        <input
            v-model="search"
            type="search"
            placeholder="Search name, email, subject…"
            class="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm"
        >
        <select
            v-model="status"
            class="rounded-md border border-gray-300 px-3 py-2 text-sm"
        >
            <option value="">All</option>
            <option value="unread">Unread</option>
            <option value="read">Read</option>
        </select>
    </div>

    <div class="grid gap-6 lg:grid-cols-5">
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm lg:col-span-3">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-10 px-3 py-2">
                            <input
                                type="checkbox"
                                :checked="allOnPageSelected"
                                class="rounded border-gray-300"
                                @change="toggleAll"
                            >
                        </th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">From</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Subject</th>
                        <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="row in enquiries.data"
                        :key="row.id"
                        class="cursor-pointer hover:bg-gray-50"
                        :class="selected?.id === row.id ? 'bg-indigo-50' : ''"
                        @click="openEnquiry(row.id)"
                    >
                        <td class="px-3 py-3" @click.stop>
                            <input
                                type="checkbox"
                                :checked="selectedIds.includes(row.id)"
                                class="rounded border-gray-300"
                                @change="toggleOne(row.id)"
                            >
                        </td>
                        <td class="px-3 py-3 text-sm" :class="!row.read_at ? 'font-semibold text-gray-900' : 'text-gray-700'">
                            <span class="inline-flex items-center gap-2">
                                <span
                                    v-if="!row.read_at"
                                    class="inline-block h-2 w-2 shrink-0 rounded-full bg-indigo-500"
                                    aria-hidden="true"
                                />
                                {{ row.name }}
                            </span>
                            <div class="text-xs font-normal text-gray-500">{{ row.email }}</div>
                        </td>
                        <td class="px-3 py-3 text-sm" :class="!row.read_at ? 'font-semibold text-gray-900' : 'text-gray-600'">
                            {{ row.subject }}
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-xs text-gray-500">
                            {{ formatDate(row.created_at) }}
                        </td>
                    </tr>
                    <tr v-if="!enquiries.data.length">
                        <td colspan="4" class="px-4 py-8 text-center text-sm text-gray-500">No enquiries yet.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="lg:col-span-2">
            <div v-if="selected" class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="mb-4 flex items-start justify-between gap-2">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">{{ selected.subject }}</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            From <strong>{{ selected.name }}</strong>
                            &lt;{{ selected.email }}&gt;
                        </p>
                        <p v-if="selected.phone" class="text-sm text-gray-500">Phone: {{ selected.phone }}</p>
                        <p class="mt-1 text-xs text-gray-400">{{ formatDate(selected.created_at) }}</p>
                    </div>
                    <button type="button" class="text-sm text-gray-500 hover:text-gray-800" @click="closeDetail">Close</button>
                </div>

                <div class="mb-4 whitespace-pre-wrap rounded-lg bg-gray-50 p-4 text-sm text-gray-800">{{ selected.message }}</div>

                <div class="flex flex-wrap gap-2">
                    <a
                        :href="mailtoHref(selected)"
                        class="rounded-lg bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700"
                    >
                        Reply
                    </a>
                    <Can permission="enquiries.update">
                        <button
                            v-if="selected.read_at"
                            type="button"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                            @click="markUnread(selected.id)"
                        >
                            Mark unread
                        </button>
                        <button
                            v-else
                            type="button"
                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
                            @click="markRead(selected.id)"
                        >
                            Mark read
                        </button>
                    </Can>
                    <Can permission="enquiries.delete">
                        <button
                            type="button"
                            class="rounded-lg border border-rose-200 px-3 py-1.5 text-sm text-rose-600 hover:bg-rose-50"
                            @click="destroy(selected.id)"
                        >
                            Delete
                        </button>
                    </Can>
                </div>
            </div>
            <div v-else class="rounded-xl border border-dashed border-gray-200 bg-gray-50 p-8 text-center text-sm text-gray-500">
                Select an enquiry to read it.
            </div>
        </div>
    </div>
</template>
