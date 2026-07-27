<script setup lang="ts">
// Atom: text input. No business logic. Dark-admin-aware via admin.css overrides.
interface Props {
    modelValue?: string | number
    type?: string
    placeholder?: string
    disabled?: boolean
    autocomplete?: string
    id?: string
    inputmode?: 'none' | 'text' | 'decimal' | 'numeric' | 'tel' | 'search' | 'email' | 'url'
    invalid?: boolean
}

withDefaults(defineProps<Props>(), {
    modelValue: '',
    type: 'text',
    placeholder: '',
    disabled: false,
    autocomplete: 'off',
    id: undefined,
    inputmode: undefined,
    invalid: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

function onInput(e: Event) {
    const target = e.target as HTMLInputElement
    emit('update:modelValue', target.value)
}
</script>

<template>
    <input
        :id="id"
        :type="type"
        :value="modelValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :autocomplete="autocomplete"
        :inputmode="inputmode"
        :aria-invalid="invalid"
        class="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2"
        :class="invalid
            ? 'border-rose-400 bg-rose-50 focus:ring-rose-200'
            : 'border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-200 disabled:bg-gray-100'"
        @input="onInput"
    >
</template>
