<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, useForm } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    user: Object,
})

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    password_confirmation: '',
})

const submit = () => {
    form.put('/profile', {
        onSuccess: () => form.reset('password', 'password_confirmation'),
    })
}
</script>

<template>
    <Head title="Profile" />

    <section class="py-16">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-2xl font-bold text-gray-900 mb-6">Your Profile</h1>

            <div class="bg-white rounded-lg shadow p-6">
                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Email</label>
                        <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">New Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                        <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                        <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Confirm Password</label>
                        <input v-model="form.password_confirmation" type="password" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    </div>
                    <div class="flex justify-end pt-4">
                        <button type="submit" :disabled="form.processing" class="px-6 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                            Update Profile
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</template>
