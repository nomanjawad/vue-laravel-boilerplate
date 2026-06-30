<script setup>
import { ref, watch } from 'vue'
import { usePage } from '@inertiajs/vue3'

const page = usePage()
const toasts = ref([])
let seq = 0

const TONES = {
    success: 'bg-emerald-50 text-emerald-800 border-emerald-200',
    error: 'bg-rose-50 text-rose-800 border-rose-200',
    info: 'bg-sky-50 text-sky-800 border-sky-200',
    warning: 'bg-amber-50 text-amber-800 border-amber-200',
}

function push(tone, message) {
    if (!message) return
    const id = ++seq
    toasts.value.push({ id, tone, message })
    setTimeout(() => dismiss(id), tone === 'error' ? 8000 : 4000)
}

function dismiss(id) {
    toasts.value = toasts.value.filter((t) => t.id !== id)
}

// React to flash bag changes from the Inertia visit.
watch(
    () => page.props.flash,
    (flash) => {
        if (!flash) return
        if (flash.success) push('success', flash.success)
        if (flash.error) push('error', flash.error)
        if (flash.info) push('info', flash.info)
        if (flash.warning) push('warning', flash.warning)
    },
    { deep: true, immediate: true },
)
</script>

<template>
    <div class="pointer-events-none fixed right-4 top-4 z-50 flex flex-col gap-2">
        <transition-group name="toast">
            <div
                v-for="t in toasts"
                :key="t.id"
                class="pointer-events-auto max-w-sm rounded-lg border px-4 py-2 text-sm shadow-md"
                :class="TONES[t.tone] ?? TONES.info"
            >
                <div class="flex items-start gap-3">
                    <p class="flex-1">{{ t.message }}</p>
                    <button class="text-xs opacity-60 hover:opacity-100" @click="dismiss(t.id)">×</button>
                </div>
            </div>
        </transition-group>
    </div>
</template>

<style scoped>
.toast-enter-from { opacity: 0; transform: translateY(-6px); }
.toast-leave-to { opacity: 0; transform: translateX(8px); }
.toast-enter-active, .toast-leave-active { transition: all .2s ease; }
</style>
