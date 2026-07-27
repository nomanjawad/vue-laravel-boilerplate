<script setup lang="ts">
interface Props {
    modelValue?: string
    rows?: number
    placeholder?: string
    disabled?: boolean
    id?: string
    invalid?: boolean
}

withDefaults(defineProps<Props>(), {
    modelValue: '',
    rows: 6,
    placeholder: '',
    disabled: false,
    id: undefined,
    invalid: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

function onInput(e: Event) {
    const target = e.target as HTMLTextAreaElement
    emit('update:modelValue', target.value)
}
</script>

<template>
    <textarea
        :id="id"
        :rows="rows"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :aria-invalid="invalid"
        class="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2"
        :class="invalid
            ? 'border-rose-400 bg-rose-50 focus:ring-rose-200'
            : 'border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-200 disabled:bg-gray-100'"
        @input="onInput"
    />
</template>
