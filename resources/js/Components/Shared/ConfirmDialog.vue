<script setup>
import { useConfirm } from '@/Composables/useConfirm.js'

const { _state } = useConfirm()

function resolve(answer) {
    _state.value?.resolve?.(answer)
    _state.value = null
}
</script>

<template>
    <div
        v-if="_state"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        @click.self="resolve(false)"
    >
        <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
            <h3 class="text-lg font-semibold text-gray-900">{{ _state.title }}</h3>
            <p v-if="_state.body" class="mt-2 text-sm text-gray-600">{{ _state.body }}</p>
            <div class="mt-5 flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded border border-gray-300 px-3 py-1.5 text-sm text-gray-700"
                    @click="resolve(false)"
                >
                    Cancel
                </button>
                <button
                    type="button"
                    class="rounded px-3 py-1.5 text-sm font-medium text-white"
                    :class="_state.confirmTone === 'danger' ? 'bg-rose-600 hover:bg-rose-700' : 'bg-indigo-600 hover:bg-indigo-700'"
                    @click="resolve(true)"
                >
                    {{ _state.confirmLabel }}
                </button>
            </div>
        </div>
    </div>
</template>
