<script setup>
import { usePage, Link, Head } from '@inertiajs/vue3'
import { computed } from 'vue'

const page = usePage()

const headerMenus = computed(() => page.props.menus?.header || [])
const footerMenus = computed(() => page.props.menus?.footer || [])
const settings = computed(() => page.props.settings || {})
const appName = computed(() => settings.value.site_name || 'WebTemplate')
</script>

<template>
    <div class="min-h-screen flex flex-col bg-white">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <Link href="/" class="text-xl font-bold text-gray-900">
                        {{ appName }}
                    </Link>
                    <nav class="hidden md:flex items-center space-x-8">
                        <Link
                            v-for="item in headerMenus"
                            :key="item.id"
                            :href="item.url"
                            class="text-sm font-medium text-gray-700 hover:text-gray-900 transition-colors"
                        >
                            {{ item.title }}
                        </Link>
                    </nav>
                    <div class="flex items-center space-x-4">
                        <template v-if="page.props.auth?.user">
                            <Link href="/profile" class="text-sm text-gray-700 hover:text-gray-900">
                                {{ page.props.auth.user.name }}
                            </Link>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                class="text-sm text-gray-500 hover:text-gray-700"
                            >
                                Logout
                            </Link>
                        </template>
                        <template v-else>
                            <Link href="/login" class="text-sm text-gray-700 hover:text-gray-900">
                                Login
                            </Link>
                            <Link href="/register" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 transition-colors">
                                Register
                            </Link>
                        </template>
                    </div>
                </div>
            </div>
        </header>

        <!-- Flash Messages -->
        <div v-if="page.props.flash?.success" class="bg-green-50 border-l-4 border-green-400 p-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-green-700">{{ page.props.flash.success }}</p>
            </div>
        </div>
        <div v-if="page.props.flash?.error" class="bg-red-50 border-l-4 border-red-400 p-4">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <p class="text-sm text-red-700">{{ page.props.flash.error }}</p>
            </div>
        </div>

        <!-- Main Content -->
        <main class="flex-1">
            <slot />
        </main>

        <!-- Footer -->
        <footer class="bg-gray-900 text-white">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div>
                        <h3 class="text-lg font-semibold mb-4">{{ appName }}</h3>
                        <p class="text-gray-400 text-sm">{{ settings.site_description || 'Building amazing websites.' }}</p>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li v-for="item in footerMenus" :key="item.id">
                                <Link :href="item.url" class="text-gray-400 hover:text-white text-sm transition-colors">
                                    {{ item.title }}
                                </Link>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Contact</h3>
                        <ul class="space-y-2 text-gray-400 text-sm">
                            <li v-if="settings.contact_email">{{ settings.contact_email }}</li>
                            <li v-if="settings.contact_phone">{{ settings.contact_phone }}</li>
                            <li v-if="settings.address">{{ settings.address }}</li>
                        </ul>
                    </div>
                </div>
                <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400 text-sm">
                    &copy; {{ new Date().getFullYear() }} {{ appName }}. All rights reserved.
                </div>
            </div>
        </footer>
    </div>
</template>
