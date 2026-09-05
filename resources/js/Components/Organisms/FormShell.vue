<script setup lang="ts">
import { provide } from 'vue'
import { Link } from '@inertiajs/vue3'
import { useUnsavedGuard } from '@/Composables/useUnsavedGuard'

type HttpMethod = 'post' | 'put' | 'patch' | 'delete'

type FormSubmit = (url: string, options?: Record<string, unknown>) => void

interface InjectedForm {
    isDirty: boolean
    processing: boolean
    errors: Record<string, string | undefined>
    post: FormSubmit
    put: FormSubmit
    patch: FormSubmit
    delete: FormSubmit
}

interface Props {
    // The useForm() instance — provided to descendants via inject('form').
    form: InjectedForm
    action: string
    method?: HttpMethod
    submitLabel?: string
    cancelHref?: string | null
    /** When true, omit the bottom Cancel/Save bar (use AppFloatingSave instead). */
    hideActions?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    method: 'post',
    submitLabel: 'Save',
    cancelHref: null,
    hideActions: false,
})

provide('form', props.form)

function submit() {
    props.form[props.method](props.action, { preserveScroll: true })
}

useUnsavedGuard(() => props.form.isDirty)
</script>

<template>
    <form class="space-y-6" @submit.prevent="submit">
        <slot />

        <div v-if="!hideActions" class="flex items-center justify-end gap-2 border-t border-gray-100 pt-4">
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
