<script setup>
import { usePage, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'
import FlashToaster from '@/Components/Shared/FlashToaster.vue'
import ConfirmDialog from '@/Components/Shared/ConfirmDialog.vue'
import NotificationBell from '@/Components/Organisms/NotificationBell.vue'

const page = usePage()
const sidebarOpen = ref(false)

const user = computed(() => page.props.auth?.user)

// Sidebar is fed by the module registry — each enabled module declares its
// own nav entries in its manifest, already permission-filtered server-side.
// Falls back to the legacy enabledFeatures map if the registry isn't shared
// yet (e.g. mid-upgrade).
const moduleNav = computed(() => page.props.modules?.nav ?? [])

const menuItems = computed(() => {
    const items = [{ title: 'Dashboard', href: '/admin', icon: 'dashboard' }]

    for (const entry of moduleNav.value) {
        // Each entry has `{ label, route, icon, permission, module }`.
        // The frontend doesn't know Laravel route names without Ziggy, so
        // entries also accept a literal `href` from manifests (used for
        // virtual modules during the v2-to-v3 transition).
        const href = entry.href || routeToHref(entry.route)
        if (!href) continue
        items.push({ title: entry.label, href, icon: entry.icon })
    }

    // Modules admin — only super-admins manage modules.
    if (user.value?.is_super_admin || user.value?.roles?.includes('admin')) {
        items.push({ title: 'Modules', href: '/admin/modules', icon: 'modules' })
    }

    return items
})

function routeToHref(name) {
    if (!name) return null
    // Conservative name → URL fallback until Ziggy lands in Phase C.
    // Pattern:  admin.<segment>.index  →  /admin/<segment with dashes>.
    const m = name.match(/^admin\.([\w-]+(?:\.[\w-]+)*)\.index$/)
    if (!m) return null
    return '/admin/' + m[1].replace(/\./g, '/').replace(/_/g, '-')
}

const isActive = (href) => {
    if (href === '/admin') return page.url === '/admin'
    return page.url.startsWith(href)
}

const logout = () => {
    router.post('/logout')
}
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
            <nav class="mt-4 px-3 space-y-1 overflow-y-auto" style="max-height: calc(100vh - 4rem)">
                <Link
                    v-for="item in menuItems"
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
    </div>
</template>
