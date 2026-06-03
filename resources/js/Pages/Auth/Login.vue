<script setup>
import AuthLayout from '@/Layouts/AuthLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AuthLayout })

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

const submit = () => {
    form.post('/login', {
        onFinish: () => form.reset('password'),
    })
}
</script>

<template>
    <Head title="Login" />

    <h2 class="text-2xl font-bold text-gray-900 mb-6">Sign in to your account</h2>

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

        <div class="flex items-center justify-between">
            <label class="flex items-center">
                <input v-model="form.remember" type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500" />
                <span class="ml-2 text-sm text-gray-600">Remember me</span>
            </label>
            <Link href="/forgot-password" class="text-sm text-gray-600 hover:text-gray-900">
                Forgot password?
            </Link>
        </div>

        <button
            type="submit"
            :disabled="form.processing"
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 disabled:opacity-50"
        >
            Sign in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-600">
        Don't have an account?
        <Link href="/register" class="font-medium text-gray-900 hover:text-gray-700">Register</Link>
    </p>
</template>
