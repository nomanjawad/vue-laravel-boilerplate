<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    members: Array,
})

const deleteMember = (member) => {
    if (confirm(`Remove "${member.name}" from the team?`)) {
        router.delete(`/admin/teams/${member.id}`)
    }
}
</script>

<template>
    <Head title="Team Members" />
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Team Members</h1>
        <Link href="/admin/teams/create" class="inline-flex items-center px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            Add Member
        </Link>
    </div>

    <div v-if="members.length" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div v-for="member in members" :key="member.id" class="bg-white rounded-lg shadow overflow-hidden">
            <div class="aspect-square bg-gray-100">
                <img v-if="member.photo" :src="member.photo" :alt="member.name" class="w-full h-full object-cover" />
                <div v-else class="w-full h-full flex items-center justify-center">
                    <svg class="w-16 h-16 text-gray-300" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
                    </svg>
                </div>
            </div>
            <div class="p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">{{ member.name }}</h3>
                        <p class="text-sm text-gray-500">{{ member.position }}</p>
                    </div>
                    <span :class="[
                        'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                        member.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'
                    ]">{{ member.is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <div class="mt-3 flex items-center space-x-3 text-sm">
                    <Link :href="`/admin/teams/${member.id}/edit`" class="text-gray-600 hover:text-gray-900">Edit</Link>
                    <button @click="deleteMember(member)" class="text-red-600 hover:text-red-900">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <div v-else class="bg-white rounded-lg shadow p-12 text-center">
        <p class="text-gray-500">No team members yet.</p>
        <Link href="/admin/teams/create" class="inline-flex items-center mt-4 px-4 py-2 bg-gray-900 text-white text-sm font-medium rounded-md hover:bg-gray-800">
            Add First Member
        </Link>
    </div>
</template>
