<script setup lang="ts">
import { usePage, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import type { SharedPageProps } from '@/types/inertia'
import AppIcon from '@/Components/Atoms/AppIcon.vue'
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
const userMenuOpen = ref(false)

const user = computed(() => page.props.auth?.user)
const siteName = computed(() => page.props.settings?.site_name || 'Admin')

const userInitials = computed(() => {
    const name = user.value?.name ?? ''
    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0]!.toUpperCase())
        .join('') || '?'
})

const userRole = computed(() => {
    if (user.value?.is_super_admin) return 'Super admin'
    const role = user.value?.roles?.[0]
    return role ? role.charAt(0).toUpperCase() + role.slice(1) : 'User'
})

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

// Breadcrumb derived from the URL: /admin/page-content/layout →
// Page content / Layout. Only the first crumb links (its index page is the
// one URL guaranteed to exist); numeric segments render as "#id".
const breadcrumbs = computed(() => {
    const path = page.url.split('?')[0] ?? ''
    const segments = path.split('/').filter(Boolean).slice(1) // drop leading "admin"
    return segments.map((seg, i) => ({
        label: /^\d+$/.test(seg)
            ? `#${seg}`
            : seg.replace(/-/g, ' ').replace(/^\w/, (c) => c.toUpperCase()),
        href: i === 0 ? `/admin/${seg}` : null,
        last: i === segments.length - 1,
    }))
})

const navItemClass = (active: boolean) => [
    'group flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
    active
        ? 'bg-brand-600/20 text-white'
        : 'text-gray-400 hover:bg-white/5 hover:text-gray-100',
]

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
            class="fixed inset-0 z-40 bg-black/60 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        />

        <!-- Sidebar -->
        <aside
            :class="[
                'fixed inset-y-0 left-0 z-50 flex w-64 flex-col border-r border-(--admin-border) bg-(--admin-sidebar) transition-transform duration-200 lg:translate-x-0',
                sidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]"
        >
            <!-- Brand -->
            <div class="flex h-16 shrink-0 items-center justify-between border-b border-(--admin-border) px-4">
                <Link href="/admin" class="flex min-w-0 items-center gap-3">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-gradient-to-br from-brand-500 to-brand-700 text-base font-bold text-white shadow-lg shadow-brand-900/40">
                        {{ siteName.charAt(0).toUpperCase() }}
                    </span>
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-white">{{ siteName }}</span>
                        <span class="block text-[11px] font-medium uppercase tracking-wider text-gray-500">Admin panel</span>
                    </span>
                </Link>
                <button
                    class="text-gray-500 hover:text-white lg:hidden"
                    aria-label="Close menu"
                    @click="sidebarOpen = false"
                >
                    <AppIcon name="x-mark" :size="22" />
                </button>
            </div>

            <!-- Nav -->
            <nav class="admin-scroll flex-1 space-y-6 overflow-y-auto px-3 py-4">
                <div class="space-y-0.5">
                    <Link :href="dashboardItem.href" :class="navItemClass(isActive(dashboardItem.href))">
                        <AppIcon
                            :name="dashboardItem.icon"
                            :size="18"
                            :class="isActive(dashboardItem.href) ? 'text-brand-300' : 'text-gray-500 group-hover:text-gray-300'"
                        />
                        {{ dashboardItem.title }}
                    </Link>
                </div>

                <div v-for="section in menuSections" :key="section.key">
                    <p class="px-3 pb-1.5 text-[11px] font-semibold uppercase tracking-widest text-gray-600">
                        {{ section.label }}
                    </p>
                    <div class="space-y-0.5">
                        <Link
                            v-for="item in section.items"
                            :key="item.href"
                            :href="item.href"
                            :class="navItemClass(isActive(item.href))"
                        >
                            <AppIcon
                                :name="item.icon"
                                :size="18"
                                :class="isActive(item.href) ? 'text-brand-300' : 'text-gray-500 group-hover:text-gray-300'"
                            />
                            <span class="truncate">{{ item.title }}</span>
                        </Link>
                    </div>
                </div>
            </nav>

            <!-- User card -->
            <div class="shrink-0 border-t border-(--admin-border) p-3">
                <div class="flex items-center gap-3 rounded-lg px-2 py-1.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600/25 text-sm font-semibold text-brand-300">
                        {{ userInitials }}
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-gray-200">{{ user?.name }}</span>
                        <span class="block truncate text-xs text-gray-500">{{ userRole }}</span>
                    </span>
                    <button
                        class="shrink-0 rounded-md p-1.5 text-gray-500 transition-colors hover:bg-white/5 hover:text-gray-200"
                        title="Log out"
                        aria-label="Log out"
                        @click="logout"
                    >
                        <AppIcon name="arrow-right-start-on-rectangle" :size="18" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Main area -->
        <div class="lg:pl-64">
            <!-- Top bar -->
            <header class="sticky top-0 z-30 border-b border-(--admin-border) bg-(--admin-bg)/85 backdrop-blur">
                <div class="flex h-16 items-center gap-3 px-4 sm:px-6 lg:px-8">
                    <button
                        class="text-gray-400 hover:text-white lg:hidden"
                        aria-label="Open menu"
                        @click="sidebarOpen = true"
                    >
                        <AppIcon name="bars-3" :size="22" />
                    </button>

                    <!-- Breadcrumbs -->
                    <nav class="hidden min-w-0 items-center gap-1.5 text-sm sm:flex" aria-label="Breadcrumb">
                        <Link href="/admin" class="shrink-0 text-gray-500 transition-colors hover:text-gray-200">
                            Dashboard
                        </Link>
                        <template v-for="(crumb, i) in breadcrumbs" :key="i">
                            <AppIcon name="chevron-right" :size="12" class="shrink-0 text-gray-600" />
                            <Link
                                v-if="crumb.href && !crumb.last"
                                :href="crumb.href"
                                class="shrink-0 text-gray-500 transition-colors hover:text-gray-200"
                            >
                                {{ crumb.label }}
                            </Link>
                            <span v-else class="truncate" :class="crumb.last ? 'font-medium text-gray-200' : 'text-gray-500'">
                                {{ crumb.label }}
                            </span>
                        </template>
                    </nav>

                    <div class="flex-1" />

                    <!-- Search -->
                    <button
                        type="button"
                        class="hidden w-56 items-center gap-2 rounded-lg border border-(--admin-border-strong) bg-(--admin-surface-sunken) px-3 py-1.5 text-sm text-gray-500 transition-colors hover:border-gray-500 hover:text-gray-300 md:flex"
                        @click="openGlobalSearch"
                    >
                        <AppIcon name="magnifying-glass" :size="15" />
                        <span class="flex-1 text-left">Search…</span>
                        <kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 font-mono text-[10px]">/</kbd>
                    </button>
                    <button
                        type="button"
                        class="rounded-md p-2 text-gray-400 transition-colors hover:bg-white/5 hover:text-white md:hidden"
                        aria-label="Search"
                        @click="openGlobalSearch"
                    >
                        <AppIcon name="magnifying-glass" :size="19" />
                    </button>

                    <a
                        href="/"
                        target="_blank"
                        class="flex items-center gap-1.5 rounded-md p-2 text-sm text-gray-400 transition-colors hover:bg-white/5 hover:text-white"
                        title="View site"
                    >
                        <AppIcon name="arrow-top-right-on-square" :size="17" />
                        <span class="hidden xl:inline">View site</span>
                    </a>

                    <NotificationBell />

                    <!-- User menu -->
                    <div class="relative">
                        <button
                            type="button"
                            class="flex items-center gap-2 rounded-full transition-opacity hover:opacity-80"
                            aria-label="User menu"
                            @click="userMenuOpen = !userMenuOpen"
                        >
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-gradient-to-br from-brand-500 to-brand-700 text-xs font-semibold text-white">
                                {{ userInitials }}
                            </span>
                            <AppIcon name="chevron-down" :size="13" class="hidden text-gray-500 sm:block" />
                        </button>

                        <template v-if="userMenuOpen">
                            <div class="fixed inset-0 z-40" @click="userMenuOpen = false" />
                            <div class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-xl border border-(--admin-border-strong) bg-(--admin-surface-raised) shadow-xl shadow-black/40 backdrop-blur">
                                <div class="border-b border-(--admin-border) px-4 py-3">
                                    <p class="truncate text-sm font-medium text-gray-100">{{ user?.name }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ user?.email }}</p>
                                </div>
                                <div class="p-1.5">
                                    <a
                                        href="/"
                                        target="_blank"
                                        class="flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-gray-300 transition-colors hover:bg-white/5 hover:text-white"
                                        @click="userMenuOpen = false"
                                    >
                                        <AppIcon name="arrow-top-right-on-square" :size="16" class="text-gray-500" />
                                        View site
                                    </a>
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-gray-300 transition-colors hover:bg-white/5 hover:text-white"
                                        @click="shortcutHelpOpen = true; userMenuOpen = false"
                                    >
                                        <AppIcon name="bolt" :size="16" class="text-gray-500" />
                                        Keyboard shortcuts
                                    </button>
                                    <button
                                        type="button"
                                        class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm text-red-400 transition-colors hover:bg-red-500/10"
                                        @click="logout"
                                    >
                                        <AppIcon name="arrow-right-start-on-rectangle" :size="16" />
                                        Log out
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <div class="mx-auto w-full max-w-7xl">
                    <slot />
                </div>
            </main>
        </div>

        <FlashToaster />
        <ConfirmDialog />
        <GlobalSearch :open="globalSearchOpen" @close="globalSearchOpen = false" />

        <!-- Keyboard shortcut help -->
        <div
            v-if="shortcutHelpOpen"
            class="fixed inset-0 z-[60] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm"
            @click.self="shortcutHelpOpen = false"
        >
            <div class="w-full max-w-sm rounded-xl border border-(--admin-border-strong) bg-(--admin-surface-raised) p-6 shadow-2xl backdrop-blur">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-100">Keyboard shortcuts</h2>
                    <button
                        type="button"
                        class="rounded-md p-1 text-gray-500 transition-colors hover:bg-white/5 hover:text-gray-200"
                        aria-label="Close"
                        @click="shortcutHelpOpen = false"
                    >
                        <AppIcon name="x-mark" :size="18" />
                    </button>
                </div>
                <dl class="space-y-2.5 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Go to home</dt>
                        <dd><kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">g</kbd> <kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">h</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Go to dashboard</dt>
                        <dd><kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">g</kbd> <kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">d</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Go to posts</dt>
                        <dd><kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">g</kbd> <kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">p</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Global search</dt>
                        <dd><kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">/</kbd></dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-gray-400">Show this help</dt>
                        <dd><kbd class="rounded border border-(--admin-border) bg-white/5 px-1.5 py-0.5 font-mono text-xs">?</kbd></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</template>
