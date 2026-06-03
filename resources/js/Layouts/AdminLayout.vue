<script setup>
import { usePage, Link, router } from '@inertiajs/vue3'
import { ref, computed } from 'vue'

const page = usePage()
const sidebarOpen = ref(false)

const user = computed(() => page.props.auth?.user)
const features = computed(() => page.props.enabledFeatures || {})

const menuItems = computed(() => {
    const items = [
        { title: 'Dashboard', href: '/admin', icon: 'dashboard' },
        { title: 'Users', href: '/admin/users', icon: 'users' },
    ]

    if (features.value.blog) {
        items.push(
            { title: 'Posts', href: '/admin/posts', icon: 'posts' },
            { title: 'Categories', href: '/admin/categories', icon: 'categories' },
            { title: 'Tags', href: '/admin/tags', icon: 'tags' },
        )
    }

    if (features.value.shop) {
        items.push(
            { title: 'Products', href: '/admin/products', icon: 'products' },
            { title: 'Orders', href: '/admin/orders', icon: 'orders' },
        )
    }

    if (features.value.careers) {
        items.push({ title: 'Careers', href: '/admin/careers', icon: 'careers' })
    }

    if (features.value.case_studies) {
        items.push({ title: 'Case Studies', href: '/admin/case-studies', icon: 'case-studies' })
    }

    if (features.value.teams) {
        items.push({ title: 'Team', href: '/admin/teams', icon: 'team' })
    }

    items.push(
        { title: 'Media', href: '/admin/media', icon: 'media' },
        { title: 'Menus', href: '/admin/menus', icon: 'menus' },
        { title: 'Pages SEO', href: '/admin/page-metas', icon: 'seo' },
        { title: 'Settings', href: '/admin/settings', icon: 'settings' },
    )

    return items
})

const isActive = (href) => {
    if (href === '/admin') return page.url === '/admin'
    return page.url.startsWith(href)
}

const logout = () => {
    router.post('/logout')
}
</script>

<template>
    <div class="min-h-screen bg-gray-100">
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

            <!-- Flash Messages -->
            <div v-if="page.props.flash?.success" class="mx-4 sm:mx-6 lg:mx-8 mt-4">
                <div class="bg-green-50 border-l-4 border-green-400 p-4 rounded">
                    <p class="text-sm text-green-700">{{ page.props.flash.success }}</p>
                </div>
            </div>
            <div v-if="page.props.flash?.error" class="mx-4 sm:mx-6 lg:mx-8 mt-4">
                <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded">
                    <p class="text-sm text-red-700">{{ page.props.flash.error }}</p>
                </div>
            </div>

            <!-- Page Content -->
            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
