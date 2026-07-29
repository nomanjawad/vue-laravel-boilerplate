<script setup lang="ts">
import { usePage, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { SharedPageProps } from '@/types/inertia'
import FlashToaster from '@/Components/Shared/FlashToaster.vue'
import ConfirmDialog from '@/Components/Shared/ConfirmDialog.vue'
import NotificationBell from '@/Components/Organisms/NotificationBell.vue'
import GlobalSearch from '@/Components/Organisms/GlobalSearch.vue'
import { useShortcuts } from '@/Composables/useShortcuts.js'

interface MenuItem {
    title: string
    href: string
    icon: string
}

interface MenuSection {
    key: string
    label: string
    items: MenuItem[]
}

// Fixed display order + human labels for the groups a module manifest can
// declare via `nav_group` (see ModuleManager::navFor()). An unrecognized or
// missing group falls back to 'content' so a module never silently vanishes
// from the sidebar.
const SECTION_ORDER: { key: string; label: string }[] = [
    { key: 'content', label: 'Content' },
    { key: 'commerce', label: 'Commerce' },
    { key: 'system', label: 'System' },
]

const page = usePage<SharedPageProps>()
const sidebarOpen = ref(false)
const shortcutHelpOpen = ref(false)
const globalSearchOpen = ref(false)

const user = computed(() => page.props.auth?.user)

// Sidebar is fed by the module registry — each enabled module declares its
// own nav entries (+ which section they belong to) in its manifest, already
// permission-filtered server-side.
const moduleNav = computed<App.Data.ModuleNavEntry[]>(() => page.props.modules?.nav ?? [])

const menuSections = computed<MenuSection[]>(() => {
    const buckets: Record<string, MenuItem[]> = { content: [], commerce: [], system: [] }

    for (const entry of moduleNav.value) {
        // Route names are resolved to hrefs server-side (ModuleManager::navFor);
        // an unresolvable entry arrives with a null href and is skipped.
        if (!entry.href) continue
        const group = buckets[entry.group] ? entry.group : 'content'
        buckets[group]!.push({ title: entry.label, href: entry.href, icon: entry.icon })
    }

    if (user.value?.is_super_admin || user.value?.roles?.includes('admin')) {
        buckets.system!.push({ title: 'Modules', href: '/admin/modules', icon: 'modules' })
    }

    return SECTION_ORDER
        .map(({ key, label }) => ({ key, label, items: buckets[key] ?? [] }))
        .filter((section) => section.items.length > 0)
})

const dashboardItem: MenuItem = { title: 'Dashboard', href: '/admin', icon: 'dashboard' }

const isActive = (href: string): boolean => {
    // The dashboard href is a prefix of every other admin URL, so it only
    // counts as active on an exact match.
    if (href === dashboardItem.href) return page.url === dashboardItem.href
    return page.url.startsWith(href)
}

const logout = () => {
    router.post('/logout')
}

function openGlobalSearch() {
    globalSearchOpen.value = true
}

useShortcuts({
    onFocusSearch: openGlobalSearch,
    onShowHelp: () => { shortcutHelpOpen.value = true },
})
</script>

<template>
    <div class="admin-dark min-h-screen">
        <!-- Mobile sidebar backdrop -->
        <div
            v-if="sidebarOpen"
            class="fixed inset-0 z-40 bg-gray-600/75 lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 transform transition-transform duration-200 lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <div class="flex items-center justify-between h-16 px-6 border-b border-gray-800">
                <Link href="/admin" class="text-white text-lg font-bold">Admin Panel</Link>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <nav class="mt-4 space-y-4 overflow-y-auto px-3 pb-4" style="max-height: calc(100vh - 4rem)">
                <div class="space-y-1">
                    <Link
                        :href="dashboardItem.href"
                        :class="[
                            'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                            isActive(dashboardItem.href)
                                ? 'bg-gray-800 text-white'
                                : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                        ]"
                    >
                        {{ dashboardItem.title }}
                    </Link>
                </div>

                <div v-for="section in menuSections" :key="section.key">
                    <p class="px-3 pb-1 text-xs font-semibold tracking-wider text-gray-500 uppercase">
                        {{ section.label }}
                    </p>
                    <div class="space-y-1">
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            :class="[
                                'flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                                isActive(item.href)
                                    ? 'bg-gray-800 text-white'
                                    : 'text-gray-300 hover:bg-gray-800 hover:text-white'
                            ]"
                        >
                            {{ item.title }}
                        </Link>
                    </div>
                </div>
            </nav>
        </aside>

        <!-- Main area -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header class="bg-white shadow-sm border-b border-gray-200">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                    <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="flex-1" />
                    <div class="flex items-center space-x-4">
                        <button
                            type="button"
                            class="hidden items-center gap-2 rounded-md border border-gray-200 px-2.5 py-1.5 text-sm text-gray-500 hover:bg-gray-50 hover:text-gray-700 sm:inline-flex"
                            @click="openGlobalSearch"
                        >
                            <span>Search</span>
                            <kbd class="rounded bg-gray-100 px-1 text-[10px] font-mono">/</kbd>
                        </button>
                        <Link href="/" class="text-sm text-gray-500 hover:text-gray-700" target="_blank">
                            View Site
                        </Link>
                        <NotificationBell />
                        <span class="text-sm text-gray-700">{{ user?.name }}</span>
                        <button
                            @click="logout"
                            class="text-sm text-gray-500 hover:text-gray-700"
                        >
                            Logout
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>

        <FlashToaster />
        <ConfirmDialog />
        <GlobalSearch :open="globalSearchOpen" @close="globalSearchOpen = false" />

        <!-- Keyboard shortcut help -->
        <div
            v-if="shortcutHelpOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 p-4"
            @click.self="shortcutHelpOpen = false"
        >
            <div class="w-full max-w-sm rounded-lg bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Keyboard shortcuts</h2>
                    <button
                        type="button"
                        class="text-gray-400 hover:text-gray-600"
                        @click="shortcutHelpOpen = false"
                    >
                        Esc
                    </button>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Go to home</dt>
                        <dd><kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">g</kbd> <kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">h</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Go to dashboard</dt>
                        <dd><kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">g</kbd> <kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">d</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Go to posts</dt>
                        <dd><kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">g</kbd> <kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">p</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Global search</dt>
                        <dd><kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">/</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-500">Show this help</dt>
                        <dd><kbd class="rounded bg-gray-100 px-1.5 py-0.5 font-mono text-xs">?</kbd></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>
