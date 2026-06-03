<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'
import { ref, watch } from 'vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    users: Object,
    filters: Object,
})

const search = ref(props.filters?.search || '')

watch(search, (value) => {
    router.get('/admin/users', { search: value }, {
        preserveState: true,
        replace: true,
    })
})

const deleteUser = (user) => {
    if (confirm(`Delete user "${user.name}"?`)) {
        router.delete(`/admin/users/${user.id}`)
    }
}
</script>

<template>
    <Head title="Users" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Users</h1>
        <Link href="/admin/users/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            Add User
        </Link>
    </div>

    <div class="bg-white rounded-lg shadow">
        <div class="p-4 border-b">
            <input
                v-model="search"
                type="text"
                placeholder="Search users..."
                class="w-full max-w-sm rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
            />
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <tr v-for="user in users.data" :key="user.id">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.name }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <span v-for="role in user.roles" :key="role.id" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                {{ role.name }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                            <Link :href="`/admin/users/${user.id}/edit`" class="text-gray-600 hover:text-gray-900 mr-3">Edit</Link>
                            <button @click="deleteUser(user)" class="text-red-600 hover:text-red-900">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!-- Pagination -->
        <div v-if="users.links && users.links.length > 3" class="px-6 py-3 border-t flex justify-end">
            <nav class="flex space-x-1">
                <Link
                    v-for="link in users.links"
                    :key="link.label"
                    :href="link.url || '#'"
                    v-html="link.label"
                    :class="[
                        'px-3 py-1 text-sm rounded',
                        link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100',
                        !link.url ? 'opacity-50 cursor-not-allowed' : ''
                    ]"
                />
            </nav>
        </div>
    </div>
</template>
