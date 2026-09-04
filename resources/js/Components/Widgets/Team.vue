<script setup lang="ts">
import AppImage from '@/Components/Atoms/AppImage.vue'

interface TeamMember {
    id?: number
    name?: string
    position?: string | null
    bio?: string | null
    photo?: string | null
}

interface Props {
    data?: { title?: string | null }
    items?: TeamMember[]
}

withDefaults(defineProps<Props>(), {
    data: () => ({}),
    items: () => [],
})
</script>

<template>
    <section v-if="items.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">{{ data?.title || 'Our Team' }}</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div v-for="member in items" :key="member.id" class="text-center">
                    <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 overflow-hidden">
                        <AppImage
                            v-if="member.photo"
                            :src="member.photo"
                            :alt="member.name || ''"
                            sizes="128px"
                            class="w-full h-full object-cover"
                        />
                    </div>
                    <h3 class="font-semibold text-gray-900">{{ member.name }}</h3>
                    <p class="text-sm text-gray-500">{{ member.position }}</p>
                    <p v-if="member.bio" class="text-sm text-gray-600 mt-2">{{ member.bio }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
