<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AuthLayout })

const props = defineProps({
    status: String,
})

const form = useForm({
    email: '',
})

const submit = () => {
    form.post('/forgot-password')
}
</script>

<template>
    <Head title="Forgot Password" />

    <h2 class="text-2xl font-bold text-gray-900 mb-2">Forgot your password?</h2>
    <p class="text-sm text-gray-600 mb-6">Enter your email and we'll send you a reset link.</p>

    <div v-if="status" class="mb-4 p-3 bg-green-50 rounded-md text-sm text-green-700">
        {{ status }}
    </div>

    <form @submit.prevent="submit" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                required
                autofocus
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50"
        >
            Send Reset Link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        <Link href="/login" class="font-medium text-gray-900 hover:text-gray-700">Back to login</Link>
    </p>
</template>
