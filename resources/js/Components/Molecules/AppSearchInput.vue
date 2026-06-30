<script setup>
import { ref, watch } from 'vue'
import AppIcon from '@/Components/Atoms/AppIcon.vue'

const props = defineProps({
    modelValue: { type: String, default: '' },
    placeholder: { type: String, default: 'Search…' },
    debounce: { type: Number, default: 250 },
})

const emit = defineEmits(['update:modelValue'])

const inner = ref(props.modelValue)
let t = null

watch(() => props.modelValue, (v) => { inner.value = v })

watch(inner, (v) => {
    clearTimeout(t)
    t = setTimeout(() => emit('update:modelValue', v), props.debounce)
})
</script>

<template>
    <div class="relative">
        <AppIcon name="seo" class="pointer-events-none absolute left-2 top-2.5 text-gray-400" :size="16" />
        <input
            v-model="inner"
            type="search"
            :placeholder="placeholder"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2 pl-8 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
    </div>
</template>
