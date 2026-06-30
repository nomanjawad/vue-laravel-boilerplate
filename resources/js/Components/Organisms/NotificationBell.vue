<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Link } from '@inertiajs/vue3'

const open = ref(false)
const items = ref([])
const unread = ref(0)
let timer = null

async function refresh() {
    try {
        const res = await fetch('/admin/notifications', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        })
        if (!res.ok) return
        const json = await res.json()
        items.value = json.recent ?? []
        unread.value = json.unread ?? 0
    } catch {
        // Silent — bell is non-critical; failures shouldn't disrupt the panel.
    }
}

async function markRead(id) {
    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
    await fetch(`/admin/notifications/${id}/read`, {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': token ?? '', Accept: 'application/json' },
    })
    refresh()
}

async function markAllRead() {
    const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
    await fetch('/admin/notifications/read-all', {
        method: 'POST',
        credentials: 'same-origin',
        headers: { 'X-CSRF-TOKEN': token ?? '', Accept: 'application/json' },
    })
    refresh()
}

onMounted(() => {
    refresh()
    timer = setInterval(refresh, 60_000)
})
onBeforeUnmount(() => clearInterval(timer))
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="relative rounded p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
            aria-label="Notifications"
            @click="open = !open"
        >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 0 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5m6 0v1a3 3 0 1 1-6 0v-1m6 0H9" />
            </svg>
            <span
                v-if="unread > 0"
                class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-semibold text-white"
            >
                {{ unread > 9 ? '9+' : unread }}
            </span>
        </button>

        <div
            v-if="open"
            class="absolute right-0 z-30 mt-1 w-80 rounded-lg border border-gray-200 bg-white shadow-lg"
            @click.stop
        >
            <div class="flex items-center justify-between border-b border-gray-100 px-3 py-2">
                <strong class="text-sm">Notifications</strong>
                <button v-if="unread" type="button" class="text-xs text-indigo-600 hover:underline" @click="markAllRead">
                    Mark all read
                </button>
            </div>

            <div v-if="!items.length" class="p-5 text-center text-sm text-gray-500">
                No notifications yet.
            </div>

            <ul v-else class="max-h-80 overflow-y-auto divide-y divide-gray-100">
                <li
                    v-for="n in items"
                    :key="n.id"
                    class="p-3"
                    :class="!n.read_at ? 'bg-indigo-50/60' : ''"
                >
                    <component
                        :is="n.href ? Link : 'div'"
                        :href="n.href"
                        class="block"
                        @click="!n.read_at && markRead(n.id)"
                    >
                        <p class="text-sm font-medium text-gray-900">{{ n.title }}</p>
                        <p v-if="n.body" class="mt-0.5 text-xs text-gray-600">{{ n.body }}</p>
                        <p class="mt-1 text-[11px] text-gray-400">{{ new Date(n.created_at).toLocaleString() }}</p>
                    </component>
                </li>
            </ul>
        </div>
    </div>
</template>
