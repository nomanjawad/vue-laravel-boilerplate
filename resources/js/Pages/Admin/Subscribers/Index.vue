<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    subscribers: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')
let timer = null
watch(search, (value) => {
    clearTimeout(timer)
    timer = setTimeout(() => {
        router.get('/admin/subscribers', value ? { search: value } : {}, { preserveState: true, replace: true })
    }, 300)
})

const remove = (id) => {
    if (confirm('Remove this subscriber?')) {
        router.delete(`/admin/subscribers/${id}`, { preserveScroll: true })
    }
}
</script>

<template>
    <Head title="Subscribers" />
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Newsletter Subscribers</h1>
        <a
            href="/admin/subscribers/export"
            class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
        >
            Export CSV
        </a>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="px-6 py-4 border-b">
            <input
                v-model="search"
                type="search"
                placeholder="Search by email…"
                class="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm"
            />
        </div>
        <div class="divide-y">
            <div v-for="sub in subscribers.data" :key="sub.id" class="px-6 py-3 flex items-center justify-between">
                <div>
                    <span class="text-sm font-medium text-gray-900">{{ sub.email }}</span>
                    <span v-if="sub.name" class="text-sm text-gray-500 ml-2">{{ sub.name }}</span>
                    <span v-if="sub.unsubscribed_at" class="text-xs text-amber-600 ml-2">unsubscribed</span>
                </div>
                <button @click="remove(sub.id)" class="text-sm text-red-600 hover:text-red-900">Delete</button>
            </div>
            <div v-if="!subscribers.data.length" class="px-6 py-4 text-sm text-gray-500">No subscribers yet.</div>
        </div>
    </div>
</template>
