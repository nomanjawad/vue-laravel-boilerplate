<script setup lang="ts">
// Generic recursive editor for arbitrary `data/*.json` content (Admin Page
// Content panel). Renders any JSON shape without knowing its schema:
//   string  -> text input, or textarea for long/multiline strings
//   number  -> number input
//   boolean -> toggle
//   object  -> a labeled group per key (recurses)
//   array of objects -> add/remove/reorder cards (recurses per item)
//   array of strings/other -> add/remove/reorder single-line inputs
//
// Key order and value types are preserved: every update replaces a value
// in-place inside a shallow copy of the parent object/array, so keys already
// present never move and never change JS type.
import { computed } from 'vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'

type JsonValue = string | number | boolean | null | JsonValue[] | { [key: string]: JsonValue }

interface Props {
    modelValue: JsonValue
    label?: string
    depth?: number
}

const props = withDefaults(defineProps<Props>(), {
    label: '',
    depth: 0,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: JsonValue): void
}>()

function keyToLabel(key: string): string {
    return key.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase())
}

function isPlainObject(v: JsonValue): v is { [key: string]: JsonValue } {
    return v !== null && typeof v === 'object' && !Array.isArray(v)
}

type Kind = 'object' | 'array' | 'string' | 'number' | 'boolean' | 'null'

const kind = computed<Kind>(() => {
    const v = props.modelValue
    if (v === null) return 'null'
    if (Array.isArray(v)) return 'array'
    if (isPlainObject(v)) return 'object'
    if (typeof v === 'number') return 'number'
    if (typeof v === 'boolean') return 'boolean'
    return 'string'
})

// --- object: one labeled row per key, in original order ---
const objectEntries = computed(() =>
    isPlainObject(props.modelValue) ? Object.entries(props.modelValue) : [],
)

function updateObjectKey(key: string, value: JsonValue) {
    const next = { ...(props.modelValue as Record<string, JsonValue>) }
    next[key] = value
    emit('update:modelValue', next)
}

// --- array ---
const arrayItems = computed<JsonValue[]>(() => (Array.isArray(props.modelValue) ? props.modelValue : []))

const arrayItemKind = computed<'object' | 'string' | 'other'>(() => {
    const items = arrayItems.value
    if (!items.length) return 'string' // empty array: default to a string-list UI
    const first = items[0]
    if (isPlainObject(first as JsonValue)) return 'object'
    if (typeof first === 'string') return 'string'
    return 'other'
})

function updateArrayIndex(index: number, value: JsonValue) {
    const next = [...arrayItems.value]
    next[index] = value
    emit('update:modelValue', next)
}

function removeArrayIndex(index: number) {
    const next = [...arrayItems.value]
    next.splice(index, 1)
    emit('update:modelValue', next)
}

function moveArrayIndex(index: number, direction: -1 | 1) {
    const next = [...arrayItems.value]
    const target = index + direction
    if (target < 0 || target >= next.length) return
    const tmp = next[index]!
    next[index] = next[target]!
    next[target] = tmp
    emit('update:modelValue', next)
}

// Blank a value while keeping its type, so a new card/line matches the
// array's established shape instead of introducing mixed types.
function blank(value: JsonValue): JsonValue {
    if (typeof value === 'string') return ''
    if (typeof value === 'number') return 0
    if (typeof value === 'boolean') return false
    if (Array.isArray(value)) return []
    if (isPlainObject(value)) {
        const clone: Record<string, JsonValue> = {}
        for (const [k, v] of Object.entries(value)) clone[k] = blank(v)
        return clone
    }
    return null
}

function addArrayItem() {
    const items = arrayItems.value
    const template = items[items.length - 1]
    const next: JsonValue = template !== undefined ? blank(template) : ''
    emit('update:modelValue', [...items, next])
}

// --- string: textarea for long/multiline content, otherwise a text input ---
function isLongString(value: string): boolean {
    return value.length > 80 || value.includes('\n')
}

function onNumberInput(raw: string) {
    const n = Number(raw)
    emit('update:modelValue', raw === '' || Number.isNaN(n) ? 0 : n)
}
</script>

<template>
    <div v-if="kind === 'object'" class="space-y-4">
        <div v-for="[key, value] in objectEntries" :key="key">
            <div
                class="mb-1 text-sm font-medium"
                :class="isPlainObject(value) || Array.isArray(value) ? 'text-gray-800 font-semibold' : 'text-gray-700'"
            >
                {{ keyToLabel(key) }}
            </div>
            <div :class="(isPlainObject(value) || Array.isArray(value)) ? 'rounded-lg border border-gray-200 bg-gray-50 p-4' : ''">
                <JsonContentEditor
                    :model-value="value"
                    :label="keyToLabel(key)"
                    :depth="depth + 1"
                    @update:model-value="(v) => updateObjectKey(key, v)"
                />
            </div>
        </div>
    </div>

    <div v-else-if="kind === 'array' && arrayItemKind === 'object'" class="space-y-4">
        <div v-for="(item, index) in arrayItems" :key="index" class="rounded-lg border border-gray-200 bg-white p-4">
            <div class="mb-3 flex items-center justify-between">
                <span class="text-xs font-semibold tracking-wide text-gray-500 uppercase">
                    {{ label || 'Item' }} #{{ index + 1 }}
                </span>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        class="text-xs text-gray-500 hover:text-gray-800 disabled:opacity-30"
                        :disabled="index === 0"
                        @click="moveArrayIndex(index, -1)"
                    >
                        ↑
                    </button>
                    <button
                        type="button"
                        class="text-xs text-gray-500 hover:text-gray-800 disabled:opacity-30"
                        :disabled="index === arrayItems.length - 1"
                        @click="moveArrayIndex(index, 1)"
                    >
                        ↓
                    </button>
                    <button type="button" class="text-xs text-rose-600 hover:underline" @click="removeArrayIndex(index)">
                        Remove
                    </button>
                </div>
            </div>
            <JsonContentEditor
                :model-value="item"
                :depth="depth + 1"
                @update:model-value="(v) => updateArrayIndex(index, v)"
            />
        </div>
        <button type="button" class="text-sm text-indigo-600 hover:underline" @click="addArrayItem">
            + Add {{ (label || 'item').toLowerCase() }}
        </button>
    </div>

    <div v-else-if="kind === 'array'" class="space-y-2">
        <div v-for="(item, index) in arrayItems" :key="index" class="flex items-center gap-2">
            <AppInput
                class="flex-1"
                :model-value="item == null ? '' : String(item)"
                @update:model-value="(v) => updateArrayIndex(index, v)"
            />
            <button
                type="button"
                class="text-xs text-gray-500 hover:text-gray-800 disabled:opacity-30"
                :disabled="index === 0"
                @click="moveArrayIndex(index, -1)"
            >
                ↑
            </button>
            <button
                type="button"
                class="text-xs text-gray-500 hover:text-gray-800 disabled:opacity-30"
                :disabled="index === arrayItems.length - 1"
                @click="moveArrayIndex(index, 1)"
            >
                ↓
            </button>
            <button type="button" class="text-xs text-rose-600 hover:underline" @click="removeArrayIndex(index)">
                Remove
            </button>
        </div>
        <button type="button" class="text-sm text-indigo-600 hover:underline" @click="addArrayItem">
            + Add line
        </button>
    </div>

    <AppSwitch
        v-else-if="kind === 'boolean'"
        :model-value="modelValue as boolean"
        @update:model-value="(v) => emit('update:modelValue', v)"
    />

    <AppInput
        v-else-if="kind === 'number'"
        type="number"
        :model-value="String(modelValue)"
        @update:model-value="onNumberInput"
    />

    <AppTextarea
        v-else-if="kind === 'string' && isLongString(modelValue as string)"
        :model-value="modelValue as string"
        :rows="4"
        @update:model-value="(v) => emit('update:modelValue', v)"
    />

    <AppInput
        v-else
        :model-value="modelValue == null ? '' : String(modelValue)"
        @update:model-value="(v) => emit('update:modelValue', v)"
    />
</template>
