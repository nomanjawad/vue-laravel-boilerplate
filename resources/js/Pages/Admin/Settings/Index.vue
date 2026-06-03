<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    settings: Object,
})

// Flatten settings into a key-value object for the form
const settingsObj = {}
Object.values(props.settings).forEach(group => {
    group.forEach(s => { settingsObj[s.key] = s.value || '' })
})

const form = useForm({
    settings: settingsObj,
})

const submit = () => {
    form.put('/admin/settings')
}

const groups = {
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
                            v-else
                            v-model="form.settings[setting.key]"
                            type="text"
                            class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        />
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
