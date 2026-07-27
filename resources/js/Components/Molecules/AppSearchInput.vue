<script setup lang="ts">
import { ref, watch } from 'vue'
import AppIcon from '@/Components/Atoms/AppIcon.vue'

interface Props {
    modelValue?: string
    placeholder?: string
    debounce?: number
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Search…',
    debounce: 250,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const inner = ref<string>(props.modelValue)
let t: ReturnType<typeof setTimeout> | null = null

watch(() => props.modelValue, (v) => { inner.value = v })

watch(inner, (v) => {
    if (t) clearTimeout(t)
    t = setTimeout(() => emit('update:modelValue', v), props.debounce)
})
</script>

<template>
    <div class="relative">
        <AppIcon name="seo" class="pointer-events-none absolute left-2 top-2.5 text-gray-400" :size="16" />
        <input
            v-model="inner"
            type="search"
            data-admin-search
            :placeholder="placeholder"
            class="w-full rounded border border-gray-300 bg-white px-3 py-2 pl-8 text-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200"
        >
    </div>
</template>
