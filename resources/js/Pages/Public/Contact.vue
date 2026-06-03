<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

defineOptions({ layout: PublicLayout })

const props = defineProps({
    data: Object,
})

const page = usePage()
const settings = computed(() => page.props.settings || {})

const form = useForm({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
})

const submit = () => {
    form.post('/contact', {
        onSuccess: () => form.reset(),
    })
}
</script>

<template>
    <Head title="Contact" />

    <!-- Hero -->
    <section class="bg-gray-900 text-white py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h1 class="text-4xl font-bold mb-4">{{ data?.hero?.title || 'Contact Us' }}</h1>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">{{ data?.hero?.subtitle }}</p>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Contact Form -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Send us a message</h2>
                    <form @submit.prevent="submit" class="space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input v-model="form.name" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input v-model="form.email" type="email" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input v-model="form.phone" type="tel" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Subject</label>
                                <input v-model="form.subject" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Message</label>
                            <textarea v-model="form.message" rows="5" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                            <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                        </div>
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto px-6 py-3 bg-gray-900 text-white font-medium rounded-md hover:bg-gray-800 disabled:opacity-50 transition-colors">
                            Send Message
                        </button>
                    </form>
                </div>

                <!-- Contact Info -->
                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">Get in touch</h2>
                    <div class="space-y-6">
                        <div v-if="settings.contact_email">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase">Email</h3>
                            <p class="mt-1 text-gray-900">{{ settings.contact_email }}</p>
                        </div>
                        <div v-if="settings.contact_phone">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase">Phone</h3>
                            <p class="mt-1 text-gray-900">{{ settings.contact_phone }}</p>
                        </div>
                        <div v-if="settings.whatsapp">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase">WhatsApp</h3>
                            <p class="mt-1 text-gray-900">{{ settings.whatsapp }}</p>
                        </div>
                        <div v-if="settings.address">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase">Address</h3>
                            <p class="mt-1 text-gray-900">{{ settings.address }}</p>
                        </div>
                        <div v-if="data?.office_hours">
                            <h3 class="text-sm font-semibold text-gray-500 uppercase">Office Hours</h3>
                            <p class="mt-1 text-gray-900">{{ data.office_hours }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
