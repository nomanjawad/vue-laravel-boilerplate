<script setup>
import { computed } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppEmptyState from '@/Components/Molecules/AppEmptyState.vue'
import AppPagination from '@/Components/Molecules/AppPagination.vue'

const props = defineProps({
    // Columns: [{ key, label, sortable?, formatter? }]
    columns: { type: Array, required: true },
    // Either a Laravel paginator JSON or a plain array of rows.
    rows: { type: [Object, Array], required: true },
    // Current sort + URL — used to render up/down arrows on sortable columns.
    sort: { type: String, default: null },
    direction: { type: String, default: 'asc' },
    emptyTitle: { type: String, default: 'No records yet.' },
    rowKey: { type: String, default: 'id' },
})

defineEmits(['rowClick'])

const data = computed(() => (Array.isArray(props.rows) ? props.rows : props.rows?.data ?? []))
const paginator = computed(() => (Array.isArray(props.rows) ? null : props.rows))

function toggleSort(col) {
    if (!col.sortable) return
    const sort = col.key
    const direction = props.sort === sort && props.direction === 'asc' ? 'desc' : 'asc'
    const url = new URL(window.location.href)
    url.searchParams.set('sort', sort)
    url.searchParams.set('direction', direction)
    router.get(url.pathname + url.search, {}, { preserveScroll: true, preserveState: true, replace: true })
}
</script>

<template>
    <div>
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500"
                            :class="{ 'cursor-pointer select-none hover:text-gray-700': col.sortable }"
                            @click="toggleSort(col)"
                        >
                            {{ col.label }}
                            <span v-if="col.sortable && sort === col.key" class="ml-1">
                                {{ direction === 'asc' ? '↑' : '↓' }}
                            </span>
                        </th>
                        <th v-if="$slots.actions" class="px-4 py-2 text-right text-xs font-medium uppercase tracking-wide text-gray-500" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <tr
                        v-for="row in data"
                        :key="row[rowKey]"
                        class="hover:bg-gray-50"
                        @click="$emit('rowClick', row)"
                    >
                        <td
                            v-for="col in columns"
                            :key="col.key"
                            class="px-4 py-2 text-sm text-gray-700"
                        >
                            <slot :name="`cell:${col.key}`" :row="row" :value="row[col.key]">
                                {{ col.formatter ? col.formatter(row[col.key], row) : row[col.key] }}
                            </slot>
                        </td>
                        <td v-if="$slots.actions" class="px-4 py-2 text-right text-sm">
                            <slot name="actions" :row="row" />
                        </td>
                    </tr>
                    <tr v-if="!data.length">
                        <td :colspan="columns.length + ($slots.actions ? 1 : 0)" class="p-0">
                            <AppEmptyState :title="emptyTitle">
                                <template v-if="$slots.empty" #action>
                                    <slot name="empty" />
                                </template>
                            </AppEmptyState>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <AppPagination v-if="paginator" :paginator="paginator" />
    </div>
</template>
