<script setup lang="ts">
interface SelectOption {
    value: string | number
    label: string
}

interface Props {
    modelValue?: string | number | null
    options?: Array<string | number | SelectOption>
    placeholder?: string
    disabled?: boolean
    id?: string
    invalid?: boolean
}

withDefaults(defineProps<Props>(), {
    modelValue: '',
    // Accepts ['a','b'] or [{ value, label }]
    options: () => [],
    placeholder: 'Select…',
    disabled: false,
    id: undefined,
    invalid: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

function normalize(opt: string | number | SelectOption): SelectOption {
    if (typeof opt === 'object' && opt !== null) return opt
    return { value: opt, label: String(opt) }
}

function onChange(e: Event) {
    const target = e.target as HTMLSelectElement
    emit('update:modelValue', target.value)
}
</script>

<template>
    <select
        :id="id"
        :value="modelValue"
        :disabled="disabled"
        :aria-invalid="invalid"
        class="w-full rounded border px-3 py-2 text-sm focus:outline-none focus:ring-2"
        :class="invalid
            ? 'border-rose-400 bg-rose-50 focus:ring-rose-200'
            : 'border-gray-300 bg-white focus:border-indigo-500 focus:ring-indigo-200 disabled:bg-gray-100'"
        @change="onChange"
    >
        <option value="" disabled>{{ placeholder }}</option>
        <option v-for="opt in options.map(normalize)" :key="opt.value" :value="opt.value">
            {{ opt.label }}
        </option>
    </select>
</template>
