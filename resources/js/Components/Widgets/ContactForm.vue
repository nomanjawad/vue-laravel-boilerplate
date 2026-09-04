<script setup lang="ts">
import { computed } from 'vue'
import { useForm, usePage } from '@inertiajs/vue3'
import type { SharedPageProps } from '@/types/inertia'

interface Props {
    data?: {
        heading?: string | null
        info_heading?: string | null
        office_hours?: string | null
        map_embed?: string | null
        show_contact_info?: boolean
    }
}

const props = withDefaults(defineProps<Props>(), {
    data: () => ({}),
})

interface ContactFormFields {
    name: string
    email: string
    phone: string
    subject: string
    message: string
    website: string
}

const page = usePage<SharedPageProps>()
const settings = computed(() => page.props.settings || ({} as App.Data.SettingsData))
const contactEnabled = computed(() => Boolean(page.props.enabledFeatures?.contact_form))

/** Inject loading="lazy" on map iframes when the paste omitted it. */
const mapHtml = computed(() => {
    const raw = props.data?.map_embed
    if (!raw) return ''
    return raw.replace(/<iframe\b([^>]*)>/gi, (_match, attrs: string) => {
        if (/\bloading\s*=/i.test(attrs)) {
            return `<iframe${attrs}>`
        }
        return `<iframe${attrs} loading="lazy">`
    })
})

const form = useForm<ContactFormFields>({
    name: '',
    email: '',
    phone: '',
    subject: '',
    message: '',
    website: '',
})

const submit = (): void => {
    form.post('/contact', { onSuccess: () => form.reset() })
}
</script>

<template>
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <div v-if="contactEnabled">
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ data?.heading || 'Send us a message' }}</h2>
                    <form class="space-y-4" @submit.prevent="submit">
                        <div class="hidden" aria-hidden="true">
                            <label>Website<input v-model="form.website" type="text" tabindex="-1" autocomplete="off" /></label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                                <input v-model="form.name" type="text" required placeholder="Jane Doe" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.name" class="mt-1 text-sm text-red-600">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Email</label>
                                <input v-model="form.email" type="email" required placeholder="name@example.com" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.email" class="mt-1 text-sm text-red-600">{{ form.errors.email }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Phone</label>
                                <input v-model="form.phone" type="tel" placeholder="+880 1XXX-XXXXXX" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.phone" class="mt-1 text-sm text-red-600">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Subject</label>
                                <input v-model="form.subject" type="text" required placeholder="How can we help?" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                                <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Message</label>
                            <textarea v-model="form.message" rows="5" required placeholder="Tell us a bit more…" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                            <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                        </div>
                        <button type="submit" :disabled="form.processing" class="w-full sm:w-auto px-6 py-3 bg-gray-900 text-white font-medium rounded-md hover:bg-gray-800 disabled:opacity-50 transition-colors">
                            Send Message
                        </button>
                    </form>
                </div>

                <div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-6">{{ data?.info_heading || 'Get in touch' }}</h2>
                    <div v-if="data?.show_contact_info !== false" class="space-y-6">
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
                    <div v-if="mapHtml" class="mt-8" v-html="mapHtml" />
                </div>
            </div>
        </div>
    </section>
</template>
