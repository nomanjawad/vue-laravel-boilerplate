<script setup lang="ts">
interface Props {
    modelValue?: boolean
    disabled?: boolean
}

withDefaults(defineProps<Props>(), {
    modelValue: false,
    disabled: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: boolean): void
}>()

function onChange(e: Event) {
    const target = e.target as HTMLInputElement
    emit('update:modelValue', target.checked)
}
</script>

<template>
    <label class="relative inline-flex cursor-pointer items-center" :class="{ 'opacity-50 pointer-events-none': disabled }">
        <input
            type="checkbox"
            class="peer sr-only"
            :checked="modelValue"
            :disabled="disabled"
            @change="onChange"
        >
        <div class="h-6 w-11 rounded-full bg-gray-300 transition-colors peer-checked:bg-indigo-600" />
        <div class="absolute top-0.5 left-0.5 h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5" />
    </label>
</template>
