<script setup>
import { computed, onMounted } from 'vue'
import { usePage } from '@inertiajs/vue3'
import { useConsentScripts } from '@/Composables/useConsentScripts'

const page = usePage()
const settings = computed(() => page.props.settings || {})

const { consent, accept, decline, initialize } = useConsentScripts()

onMounted(() => initialize(settings.value))

const text = computed(() =>
    settings.value.cookie_consent_text
    || 'We use cookies to analyse traffic and improve your experience. You can accept or decline analytics cookies.'
)
</script>

<template>
    <div
        v-if="!consent"
        class="fixed inset-x-0 bottom-0 z-50 border-t border-gray-200 bg-white/95 backdrop-blur px-4 py-4 shadow-lg"
    >
        <div class="mx-auto flex max-w-5xl flex-col gap-3 sm:flex-row sm:items-center">
            <p class="flex-1 text-sm text-gray-700">{{ text }}</p>
            <div class="flex gap-2 shrink-0">
                <button
                    class="rounded-lg border border-gray-300 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"
                    @click="decline"
                >
                    Decline
                </button>
                <button
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700"
                    @click="accept(settings)"
                >
                    Accept
                </button>
            </div>
        </div>
    </div>
</template>
