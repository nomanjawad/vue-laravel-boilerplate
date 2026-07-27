<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

interface Setting {
    key: string
    value: string | null
    type: string
    is_secret?: boolean
}

interface Props {
    settings: Record<string, Setting[]>
}

const props = defineProps<Props>()

interface SettingsForm {
    settings: Record<string, string>
}

// Flatten settings into a key-value object for the form. Secret values arrive
// from the server blanked (see Admin\SettingController::index) — we keep them
// blank so an unmodified submit is treated as "leave the existing value alone"
// server-side. Never seed a secret input with anything a user might mistake
// for the real value.
const settingsObj: Record<string, string> = {}
Object.values(props.settings).forEach((group: Setting[]) => {
    group.forEach((s: Setting) => { settingsObj[s.key] = s.value || '' })
})

const form = useForm<SettingsForm>({
    settings: settingsObj,
})

const submit = () => {
    form.put('/admin/settings')
}

const groups: Record<string, string> = {
    general: 'General',
    contact: 'Contact Information',
    social: 'Social Media',
    shop: 'Shop',
}
</script>

<template>
    <Head title="Settings" />
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Site Settings</h1>

    <form @submit.prevent="submit">
        <div v-for="(label, groupKey) in groups" :key="groupKey" class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b">
                <h2 class="text-lg font-semibold text-gray-900">{{ label }}</h2>
            </div>
            <div class="p-6 space-y-4">
                <template v-for="setting in (settings[groupKey] || [])" :key="setting.key">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 capitalize">
                            {{ setting.key.replace(/_/g, ' ') }}
                        </label>
                        <textarea
                            v-if="setting.type === 'text'"
                            v-model="form.settings[setting.key]"
                            rows="3"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        />
                        <input
                            v-else-if="setting.is_secret"
                            v-model="form.settings[setting.key]"
                            type="password"
                            autocomplete="new-password"
                            placeholder="•••••••• (leave blank to keep current)"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        />
                        <input
                            v-else
                            v-model="form.settings[setting.key]"
                            type="text"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        />
                        <p v-if="setting.is_secret" class="mt-1 text-xs text-gray-500">
                            Stored encrypted. Leave blank to keep the current value.
                        </p>
                    </div>
                </template>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                Save Settings
            </button>
        </div>
    </form>
</template>
