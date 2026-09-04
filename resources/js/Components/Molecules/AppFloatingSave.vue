<script setup lang="ts">
/**
 * Sticky bottom-right save control for long editors (pages, blog posts).
 * Shows dirty-state indicator + saving spinner; disabled when clean.
 */
interface Props {
    dirty?: boolean
    processing?: boolean
    label?: string
    dirtyLabel?: string
}

withDefaults(defineProps<Props>(), {
    dirty: false,
    processing: false,
    label: 'Save',
    dirtyLabel: 'Save changes',
})

const emit = defineEmits<{
    (e: 'save'): void
}>()
</script>

<template>
    <div class="pointer-events-none fixed bottom-6 right-6 z-[150]">
        <button
            type="button"
            class="pointer-events-auto inline-flex items-center gap-2 rounded-full px-5 py-3 text-sm font-semibold shadow-lg transition
                disabled:cursor-not-allowed disabled:opacity-50"
            :class="dirty
                ? 'bg-brand-600 text-white hover:bg-brand-700'
                : 'bg-gray-900 text-white hover:bg-gray-800'"
            :disabled="!dirty || processing"
            @click="emit('save')"
        >
            <span
                v-if="processing"
                class="inline-block h-4 w-4 animate-spin rounded-full border-2 border-white/30 border-t-white"
            />
            <span
                v-else-if="dirty"
                class="inline-block h-2 w-2 rounded-full bg-amber-300"
                aria-hidden="true"
            />
            {{ processing ? 'Saving…' : (dirty ? dirtyLabel : label) }}
        </button>
    </div>
</template>
