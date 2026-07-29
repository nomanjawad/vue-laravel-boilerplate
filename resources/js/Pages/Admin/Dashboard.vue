<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router, usePage } from '@inertiajs/vue3'
import { computed, ref } from 'vue'
import type { SharedPageProps } from '@/types/inertia'
import AppIcon from '@/Components/Atoms/AppIcon.vue'

defineOptions({ layout: AdminLayout })

interface DashboardStats {
    users?: number
    posts?: number
    products?: number
    orders?: number
}

interface NotFoundLog {
    id: number
    path: string
    hit_count: number
}

interface Props {
    stats?: DashboardStats
    topNotFound?: NotFoundLog[]
}

const props = withDefaults(defineProps<Props>(), {
    stats: () => ({}),
    topNotFound: () => [],
})

const page = usePage<SharedPageProps>()
const firstName = computed(() => (page.props.auth?.user?.name ?? '').split(' ')[0] ?? '')

const statCards = computed(() => [
    { label: 'Total users', value: props.stats.users ?? 0, icon: 'users', href: '/admin/users' },
    { label: 'Total posts', value: props.stats.posts ?? 0, icon: 'document-text', href: '/admin/posts' },
    { label: 'Total products', value: props.stats.products ?? 0, icon: 'shopping-bag', href: '/admin/products' },
    { label: 'Total orders', value: props.stats.orders ?? 0, icon: 'receipt-percent', href: '/admin/orders' },
])

const quickActions = [
    { label: 'New post', description: 'Write a blog post', icon: 'document-text', href: '/admin/posts/create' },
    { label: 'Media library', description: 'Upload & manage files', icon: 'photo', href: '/admin/media' },
    { label: 'Edit pages', description: 'Home, about, contact', icon: 'rectangle-stack', href: '/admin/page-content' },
    { label: 'Site settings', description: 'Branding, contact, SEO', icon: 'cog-6-tooth', href: '/admin/settings' },
]

const clearing = ref(false)

function clearCache() {
    clearing.value = true
    router.post('/admin/cache/clear', {}, {
        preserveScroll: true,
        onFinish: () => (clearing.value = false),
    })
}
</script>

<template>
    <Head title="Dashboard" />
    <div class="space-y-6">
        <!-- Page header -->
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Welcome back{{ firstName ? `, ${firstName}` : '' }} — here's what's happening on your site.
                </p>
            </div>
            <button
                type="button"
                :disabled="clearing"
                class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 disabled:opacity-50"
                @click="clearCache"
            >
                <AppIcon name="arrow-path-rounded-square" :size="16" />
                {{ clearing ? 'Clearing…' : 'Clear cache' }}
            </button>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <Link
                v-for="card in statCards"
                :key="card.label"
                :href="card.href"
                class="group rounded-xl border border-gray-200 bg-white p-5 shadow-sm transition-colors hover:border-gray-400"
            >
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-600/20 text-brand-300">
                        <AppIcon :name="card.icon" :size="20" />
                    </span>
                    <AppIcon
                        name="chevron-right"
                        :size="14"
                        class="text-gray-400 opacity-0 transition-opacity group-hover:opacity-100"
                    />
                </div>
                <p class="mt-4 text-3xl font-bold tracking-tight text-gray-900">{{ card.value }}</p>
                <p class="mt-1 text-sm font-medium text-gray-500">{{ card.label }}</p>
            </Link>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <!-- Quick actions -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Quick actions</h2>
                </div>
                <div class="grid grid-cols-1 gap-2 p-3 sm:grid-cols-2">
                    <Link
                        v-for="action in quickActions"
                        :key="action.label"
                        :href="action.href"
                        class="flex items-center gap-3 rounded-lg px-3 py-3 transition-colors hover:bg-gray-50"
                    >
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gray-100 text-gray-500">
                            <AppIcon :name="action.icon" :size="18" />
                        </span>
                        <span class="min-w-0">
                            <span class="block text-sm font-medium text-gray-900">{{ action.label }}</span>
                            <span class="block truncate text-xs text-gray-500">{{ action.description }}</span>
                        </span>
                    </Link>
                </div>
            </div>

            <!-- 404 monitoring: most-hit missing URLs feed the redirect manager -->
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <h2 class="text-base font-semibold text-gray-900">Top 404s</h2>
                    <Link href="/admin/redirects" class="text-sm font-medium text-indigo-400 hover:text-indigo-300">
                        Manage redirects →
                    </Link>
                </div>
                <div class="divide-y divide-gray-100">
                    <div
                        v-for="log in topNotFound"
                        :key="log.id"
                        class="flex items-center justify-between gap-4 px-5 py-3"
                    >
                        <code class="truncate font-mono text-sm text-gray-900">{{ log.path }}</code>
                        <span class="shrink-0 rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">
                            {{ log.hit_count }} hits
                        </span>
                    </div>
                    <div v-if="!topNotFound.length" class="px-5 py-10 text-center">
                        <p class="text-sm font-medium text-gray-900">No 404s logged</p>
                        <p class="mt-1 text-sm text-gray-500">Broken inbound links will show up here so you can add redirects.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
