<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    user: Object,
    roles: Array,
})

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    role: props.user.roles?.[0]?.name || 'user',
})

const submit = () => {
    form.put(`/admin/users/${props.user.id}`)
}
</script>

<template>
    <Head title="Edit User" />
    <div class="flex items-center mb-6">
        <Link href="/admin/users" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
    </div>

    <div class="bg-white rounded-lg shadow max-w-2xl">
        <form @submit.prevent="submit" class="p-6 space-y-4">
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
                <label class="block text-sm font-medium text-gray-700">Password <span class="text-gray-400">(leave blank to keep current)</span></label>
                <input v-model="form.password" type="password" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                <p v-if="form.errors.password" class="mt-1 text-sm text-red-600">{{ form.errors.password }}</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Role</label>
                <select v-model="form.role" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                    <option v-for="role in roles" :key="role.id" :value="role.name">{{ role.name }}</option>
                </select>
                <p v-if="form.errors.role" class="mt-1 text-sm text-red-600">{{ form.errors.role }}</p>
            </div>
            <div class="flex justify-end space-x-3 pt-4">
                <Link href="/admin/users" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900">Cancel</Link>
                <button type="submit" :disabled="form.processing" class="px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800 disabled:opacity-50">
                    Update User
                </button>
            </div>
        </form>
    </div>
</template>
