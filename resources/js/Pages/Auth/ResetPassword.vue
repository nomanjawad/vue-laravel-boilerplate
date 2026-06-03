<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

defineOptions({ layout: AuthLayout })

const props = defineProps({
    email: String,
    token: String,
})

const form = useForm({
    token: props.token,
    email: props.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.post('/reset-password', {
        onFinish: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Reset Password" />

    <h2 class="text-2xl font-bold text-gray-900 mb-6">Reset your password</h2>

    <form @submit.prevent="submit" class="space-y-4">
        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input
                id="email"
                v-model="form.email"
                type="email"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
            />
            <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input
                id="password"
                v-model="form.password"
                type="password"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
            />
            <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm Password</label>
            <input
                id="password_confirmation"
                v-model="form.password_confirmation"
                type="password"
                required
                class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
            />
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50"
        >
            Reset Password
        </button>
    </form>
</template>
