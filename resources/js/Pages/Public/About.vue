<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head } from '@inertiajs/vue3'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    data: Object,
    teamMembers: Array,
})
</script>

<template>
    <Head title="About" />

    <!-- Hero -->
    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold mb-4">{{ data?.hero?.title || 'About Us' }}</h1>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">{{ data?.hero?.subtitle }}</p>
        </div>
    </section>

    <!-- Mission -->
    <section v-if="data?.mission" class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ data.mission.title }}</h2>
            <p class="text-lg text-gray-600">{{ data.mission.description }}</p>
        </div>
    </section>

    <!-- Values -->
    <section v-if="data?.values?.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">Our Values</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <div v-for="(value, i) in data.values" :key="i" class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">{{ value.title }}</h3>
                    <p class="text-gray-600 text-sm">{{ value.description }}</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Story -->
    <section v-if="data?.story" class="py-16 bg-white">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ data.story.title }}</h2>
            <div class="space-y-4">
                <p v-for="(p, i) in data.story.paragraphs" :key="i" class="text-gray-600 leading-relaxed">{{ p }}</p>
            </div>
        </div>
    </section>

    <!-- Team -->
    <section v-if="teamMembers?.length" class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-2xl font-bold text-gray-900 text-center mb-12">Our Team</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <div v-for="member in teamMembers" :key="member.id" class="text-center">
                    <div class="w-32 h-32 bg-gray-200 rounded-full mx-auto mb-4 overflow-hidden">
                        <img v-if="member.photo" :src="member.photo" :alt="member.name" class="w-full h-full object-cover" />
                    </div>
                    <h3 class="font-semibold text-gray-900">{{ member.name }}</h3>
                    <p class="text-sm text-gray-500">{{ member.position }}</p>
                    <p v-if="member.bio" class="text-sm text-gray-600 mt-2">{{ member.bio }}</p>
                </div>
            </div>
        </div>
    </section>
</template>
