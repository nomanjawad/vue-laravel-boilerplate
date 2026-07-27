<script setup lang="ts">
interface Props {
    modelValue?: boolean
    disabled?: boolean
    id?: string
}

withDefaults(defineProps<Props>(), {
    modelValue: false,
    disabled: false,
    id: undefined,
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
    <input
        :id="id"
        type="checkbox"
        :checked="modelValue"
        :disabled="disabled"
        class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        @change="onChange"
    >
</template>
