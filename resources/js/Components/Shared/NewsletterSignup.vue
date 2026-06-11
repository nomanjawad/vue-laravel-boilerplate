<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({ email: '' })

const submit = () => {
    form.post('/newsletter', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <div>
        <h3 class="text-lg font-semibold mb-4">Newsletter</h3>
        <p class="text-gray-400 text-sm mb-3">Get updates straight to your inbox.</p>
        <form @submit.prevent="submit" class="flex gap-2">
            <input
                v-model="form.email"
                type="email"
                required
                placeholder="you@example.com"
                class="flex-1 rounded-md border border-gray-700 bg-gray-800 text-white placeholder-gray-500 px-3 py-2 text-sm"
            />
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                Subscribe
            </button>
        </form>
        <p v-if="form.errors.email" class="text-xs text-red-600 mt-1">{{ form.errors.email }}</p>
        <p v-if="form.recentlySuccessful" class="text-xs text-green-600 mt-1">Thanks for subscribing!</p>
    </div>
</template>
