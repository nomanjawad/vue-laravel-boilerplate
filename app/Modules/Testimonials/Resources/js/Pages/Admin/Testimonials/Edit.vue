<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

interface Testimonial {
    id: number
    title: string
    body?: string
    is_active?: boolean
    slug?: string
}

interface Props {
    testimonial: Testimonial
}

interface TestimonialForm {
    title: string
    body: string
    is_active: boolean
}

const props = defineProps<Props>()

const form = useForm<TestimonialForm>({
    title: props.testimonial.title,

    body: props.testimonial.body ?? '',
    is_active: !!props.testimonial.is_active,
})

function submit() {
    form.put(`/admin/testimonials/${props.testimonial.id}`)
}
</script>

<template>
    <Head title="Edit testimonial" />
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit testimonial</h1>

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
                Save
            </button>
            <Link href="/admin/testimonials" class="rounded border border-gray-300 px-4 py-2 text-sm text-gray-700">Cancel</Link>
        </div>
    </form>
</template>
