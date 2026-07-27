<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import { computed, ref } from 'vue'

type Primitive = string | number | boolean | null | undefined
type Payload = Record<string, Primitive | Primitive[]>

defineOptions({ layout: AdminLayout })

interface ModuleEntry {
    key: string
    name: string
    version: string
    description: string
    core: boolean
    enabled: boolean
    nav_visible: boolean
    has_nav: boolean
    unhealthy: boolean
    last_error?: string | null
    dependencies?: string[]
}

interface Props {
    modules?: ModuleEntry[]
}

const props = withDefaults(defineProps<Props>(), {
    modules: () => [],
})

type ModuleAction = 'enable' | 'disable' | 'reinstall' | 'clear-health' | 'toggle-nav' | 'uninstall'

const busy = ref<string | null>(null)
const uninstallTarget = ref<ModuleEntry | null>(null)
const uninstallConfirm = ref('')

const sorted = computed(() =>
    [...props.modules].sort((a, b) => {
        if (a.core !== b.core) return a.core ? -1 : 1
        return a.name.localeCompare(b.name)
    }),
)

function call(key: string, action: ModuleAction, method: 'post' | 'put' | 'patch' = 'post', extra: Payload = {}) {
    busy.value = `${key}:${action}`
    const url = `/admin/modules/${key}/${action}`.replace(/\/$/, '')
    const options = {
        preserveScroll: true,
        onFinish: () => (busy.value = null),
    }
    if (method === 'put') {
        router.put(url, extra, options)
    } else if (method === 'patch') {
        router.patch(url, extra, options)
    } else {
        router.post(url, extra, options)
    }
}

function toggle(mod: ModuleEntry) {
    call(mod.key, mod.enabled ? 'disable' : 'enable')
}

function reinstall(mod: ModuleEntry) {
    if (!confirm(`Re-run migrations and seeders for ${mod.name}?`)) return
    call(mod.key, 'reinstall')
}

function clearHealth(mod: ModuleEntry) {
    call(mod.key, 'clear-health')
}

// Nav visibility is independent of enable: available on core modules too,
// so a project that doesn't use page_metas / menus / etc. can hide them
// from the sidebar without disabling them (feedback.md §11).
function toggleNav(mod: ModuleEntry) {
    call(mod.key, 'toggle-nav', 'post', { visible: !mod.nav_visible })
}

function openUninstall(mod: ModuleEntry) {
    uninstallTarget.value = mod
    uninstallConfirm.value = ''
}

function confirmUninstall() {
    if (uninstallConfirm.value !== 'UNINSTALL') return
    if (!uninstallTarget.value) return
    const target = uninstallTarget.value
    busy.value = `${target.key}:uninstall`
    router.delete(`/admin/modules/${target.key}`, {
        data: { confirm: 'UNINSTALL' },
        preserveScroll: true,
        onFinish: () => {
            busy.value = null
            uninstallTarget.value = null
        },
    })
}
</script>

<template>
    <Head title="Modules" />
    <div>
        <div class="mb-6 flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Modules</h1>
                <p class="mt-1 text-sm text-gray-500">
                    Enable, disable, or uninstall feature modules. Core modules are always on.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="mod in sorted"
                :key="mod.key"
                class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm"
                :class="{ 'opacity-60': !mod.enabled, 'ring-2 ring-rose-300': mod.unhealthy }"
            >
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-semibold text-gray-900">{{ mod.name }}</h3>
                            <span
                                v-if="mod.core"
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                            >
                                Core
                            </span>
                            <span
                                v-if="mod.unhealthy"
                                class="rounded-full bg-rose-100 px-2 py-0.5 text-xs font-medium text-rose-700"
                            >
                                Unhealthy
                            </span>
                            <span
                                v-else-if="mod.enabled"
                                class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700"
                            >
                                Enabled
                            </span>
                            <span
                                v-else
                                class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600"
                            >
                                Disabled
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-gray-500">v{{ mod.version }} · {{ mod.key }}</p>
                    </div>

                    <label
                        v-if="!mod.core"
                        class="relative inline-flex cursor-pointer items-center"
                        :class="{ 'pointer-events-none opacity-50': busy }"
                    >
                        <input
                            type="checkbox"
                            class="peer sr-only"
                            :checked="mod.enabled"
                            @change="toggle(mod)"
                        >
                        <div
                            class="h-6 w-11 rounded-full bg-gray-300 transition-colors peer-checked:bg-indigo-600"
                        />
                        <div
                            class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"
                        />
                    </label>
                </div>

                <p class="mt-3 text-sm text-gray-600">{{ mod.description }}</p>

                <div v-if="mod.dependencies?.length" class="mt-3">
                    <p class="text-xs font-medium text-gray-500">Requires</p>
                    <div class="mt-1 flex flex-wrap gap-1">
                        <span
                            v-for="dep in mod.dependencies"
                            :key="dep"
                            class="rounded bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700"
                        >
                            {{ dep }}
                        </span>
                    </div>
                </div>

                <div v-if="mod.unhealthy" class="mt-3 rounded border border-rose-200 bg-rose-50 p-2">
                    <p class="text-xs font-semibold text-rose-700">Last error</p>
                    <p class="mt-0.5 text-xs text-rose-700">{{ mod.last_error }}</p>
                    <button
                        type="button"
                        class="mt-2 text-xs text-rose-700 underline"
                        :disabled="!!busy"
                        @click="clearHealth(mod)"
                    >
                        Clear health flag
                    </button>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2 text-xs">
                    <button
                        v-if="mod.enabled"
                        type="button"
                        class="rounded border border-gray-300 px-2 py-1 text-gray-700 hover:bg-gray-50"
                        :disabled="!!busy"
                        @click="reinstall(mod)"
                    >
                        Reinstall
                    </button>
                    <label
                        v-if="mod.has_nav"
                        class="flex cursor-pointer items-center gap-1.5 text-gray-600"
                        :class="{ 'pointer-events-none opacity-50': busy }"
                        :title="mod.nav_visible ? 'Hide this module from the admin sidebar' : 'Show this module in the admin sidebar'"
                    >
                        <input
                            type="checkbox"
                            class="rounded border-gray-300"
                            :checked="mod.nav_visible"
                            @change="toggleNav(mod)"
                        >
                        <span>Show in nav</span>
                    </label>
                    <button
                        v-if="!mod.core"
                        type="button"
                        class="ml-auto rounded border border-rose-300 px-2 py-1 text-rose-700 hover:bg-rose-50"
                        :disabled="!!busy"
                        @click="openUninstall(mod)"
                    >
                        Uninstall
                    </button>
                </div>
            </div>
        </div>

        <!-- Uninstall confirmation -->
        <div
            v-if="uninstallTarget"
            class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
            @click.self="uninstallTarget = null"
        >
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h3 class="text-lg font-semibold text-gray-900">
                    Uninstall {{ uninstallTarget.name }}?
                </h3>
                <p class="mt-2 text-sm text-gray-600">
                    This rolls back the module's migrations and drops its permissions.
                    <strong>Any data stored by this module will be lost.</strong>
                </p>
                <p class="mt-3 text-sm text-gray-700">
                    Type <code class="rounded bg-gray-100 px-1">UNINSTALL</code> to confirm.
                </p>
                <input
                    v-model="uninstallConfirm"
                    type="text"
                    class="mt-2 w-full rounded border border-gray-300 px-3 py-2 text-sm"
                    autofocus
                >
                <div class="mt-4 flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700"
                        @click="uninstallTarget = null"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        class="rounded bg-rose-600 px-3 py-1.5 text-sm font-medium text-white disabled:opacity-50"
                        :disabled="uninstallConfirm !== 'UNINSTALL' || !!busy"
                        @click="confirmUninstall"
                    >
                        Uninstall
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
