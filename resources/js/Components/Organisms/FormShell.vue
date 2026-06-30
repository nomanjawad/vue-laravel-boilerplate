<script setup>
import { onBeforeUnmount, provide } from 'vue'
import { Link } from '@inertiajs/vue3'

const props = defineProps({
    // The useForm() instance — provided to descendants via inject('form').
    form: { type: Object, required: true },
    action: { type: String, required: true },
    method: { type: String, default: 'post', validator: (v) => ['post', 'put', 'patch', 'delete'].includes(v) },
    submitLabel: { type: String, default: 'Save' },
    cancelHref: { type: String, default: null },
})

provide('form', props.form)

function submit() {
    props.form[props.method](props.action, { preserveScroll: true })
}

// Unsaved-change warning. Browsers ignore the message text now but still show
// the native confirmation dialog when beforeunload returns a string.
function guard(e) {
    if (props.form.isDirty) {
        e.preventDefault()
        e.returnValue = ''
    }
}
window.addEventListener('beforeunload', guard)
onBeforeUnmount(() => window.removeEventListener('beforeunload', guard))
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <slot />

        <div class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
            <Link
                v-if="cancelHref"
                :href="cancelHref"
                class="rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50"
            >
                Cancel
            </Link>
            <button
                type="submit"
                :disabled="form.processing"
                class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
            >
                {{ form.processing ? 'Saving…' : submitLabel }}
            </button>
        </div>
    </form>
</template>
