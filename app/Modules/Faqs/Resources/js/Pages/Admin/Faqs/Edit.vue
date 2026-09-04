<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

interface PageOption {
    slug: string
    title: string
}

interface Faq {
    id: number
    title: string
    page_slug?: string | null
    body?: string
    is_active?: boolean
}

interface Props {
    faq: Faq
    pages: PageOption[]
}

const props = defineProps<Props>()

interface FaqForm {
    title: string
    page_slug: string
    body: string
    is_active: boolean
}

const form = useForm<FaqForm>({
    title: props.faq.title,
    page_slug: props.faq.page_slug ?? '',
    body: props.faq.body ?? '',
    is_active: !!props.faq.is_active,
})

function submit() {
    form.put(`/admin/faqs/${props.faq.id}`)
}
</script>

<template>
    <Head title="Edit faq" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Edit faq</h1>

    <form class="max-w-2xl space-y-4" @submit.prevent="submit">
        <div>
            <label class="block text-sm font-medium text-gray-700">Title</label>
            <input
                v-model="form.title"
                type="text"
                placeholder="How do I get started?"
                class="mt-1 w-full rounded border border-gray-300 px-3 py-2"
            >
            <p v-if="form.errors.title" class="mt-1 text-xs text-rose-600">{{ form.errors.title }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Page</label>
            <select v-model="form.page_slug" class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
                <option value="">Global (all pages)</option>
                <option v-for="p in pages" :key="p.slug" :value="p.slug">{{ p.title }} ({{ p.slug }})</option>
            </select>
            <p v-if="form.errors.page_slug" class="mt-1 text-xs text-rose-600">{{ form.errors.page_slug }}</p>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Body</label>
            <textarea
                v-model="form.body"
                rows="8"
                placeholder="Write a clear, helpful answer…"
                class="mt-1 w-full rounded border border-gray-300 px-3 py-2"
            />
            <p v-if="form.errors.body" class="mt-1 text-xs text-rose-600">{{ form.errors.body }}</p>
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
                Save
            </button>
            <Link href="/admin/faqs" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</Link>
        </div>
    </form>
</template>
