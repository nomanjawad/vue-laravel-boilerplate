<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { route } from 'ziggy-js'

defineOptions({ layout: AdminLayout })

const form = useForm({
    title: '',

    body: '',
    is_active: true,
})

function submit() {
    form.post(route('admin.faqs.store'))
}
</script>

<template>
    <Head title="New faq" />
    <h1 class="text-2xl font-bold text-gray-900 mb-6">New faq</h1>

    <form class="max-w-2xl space-y-4" @submit.prevent="submit">
        <div>
            <label class="block text-sm font-medium text-gray-700">Title</label>
            <input v-model="form.title" type="text" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
        </div>



        <div>
            <label class="block text-sm font-medium text-gray-700">Body</label>
            <textarea v-model="form.body" rows="8" class="mt-1 w-full rounded border border-gray-300 px-3 py-2" />
        </div>

        <label class="flex items-center gap-2">
            <input v-model="form.is_active" type="checkbox" class="rounded border-gray-300">
            <span class="text-sm">Active</span>
        </label>

        <div class="flex gap-2">
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                Create
            </button>
            <Link :href="route('admin.faqs.index')" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</Link>
        </div>
    </form>
</template>
