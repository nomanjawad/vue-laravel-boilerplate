<script setup lang="ts">
import { computed, ref } from 'vue'
import { useForm } from '@inertiajs/vue3'

const props = defineProps<{
    modelValue: number[]
    categories: App.Data.CategorySummaryData[]
}>()

const emit = defineEmits<{
    'update:modelValue': [value: number[]]
}>()

interface FlatCategory extends App.Data.CategorySummaryData {
    depth: number
}

function flattenTree(
    cats: App.Data.CategorySummaryData[],
    parentId: number | null = null,
    depth = 0,
): FlatCategory[] {
    return cats
        .filter((c) => (c.parent_id ?? null) === parentId)
        .flatMap((c) => [
            { ...c, depth },
            ...flattenTree(cats, c.id, depth + 1),
        ])
}

const flatCategories = computed(() => flattenTree(props.categories))

function toggle(id: number) {
    const next = props.modelValue.includes(id)
        ? props.modelValue.filter((v) => v !== id)
        : [...props.modelValue, id]
    emit('update:modelValue', next)
}

const showAdd = ref(false)
const newCategory = useForm({
    name: '',
    parent_id: '' as number | '',
})

function addCategory() {
    newCategory.post('/admin/categories', {
        preserveScroll: true,
        only: ['categories'],
        onSuccess: () => {
            newCategory.reset('name', 'parent_id')
            showAdd.value = false
        },
    })
}
</script>

<template>
    <div class="space-y-3">
        <label class="block text-sm font-medium text-gray-700">Categories</label>
        <div class="max-h-48 space-y-1 overflow-y-auto rounded-md border border-gray-200 p-2">
            <label
                v-for="cat in flatCategories"
                :key="cat.id"
                class="flex items-center gap-2 py-0.5 text-sm text-gray-700"
                :style="{ paddingLeft: `${cat.depth}rem` }"
            >
                <input
                    type="checkbox"
                    class="rounded border-gray-300 text-gray-900 focus:ring-gray-500"
                    :checked="modelValue.includes(cat.id)"
                    @change="toggle(cat.id)"
                />
                <span>{{ cat.name }}</span>
            </label>
            <p v-if="!flatCategories.length" class="px-1 py-2 text-xs text-gray-400">No categories yet.</p>
        </div>

        <button
            type="button"
            class="text-sm text-indigo-600 hover:text-indigo-800"
            @click="showAdd = !showAdd"
        >
            {{ showAdd ? '− Cancel' : '+ Add New Category' }}
        </button>

        <div v-if="showAdd" class="space-y-2 rounded-md border border-gray-200 bg-gray-50 p-3">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                <input
                    v-model="newCategory.name"
                    type="text"
                    required
                    placeholder="Category name"
                    class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                    @keyup.enter.prevent="addCategory"
                />
                <p v-if="newCategory.errors.name" class="mt-1 text-xs text-red-600">{{ newCategory.errors.name }}</p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500">Parent</label>
                <select
                    v-model="newCategory.parent_id"
                    class="w-full rounded-md border border-gray-300 px-2 py-1.5 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                >
                    <option value="">— None —</option>
                    <option v-for="cat in flatCategories" :key="cat.id" :value="cat.id">
                        {{ '— '.repeat(cat.depth) }}{{ cat.name }}
                    </option>
                </select>
            </div>
            <button
                type="button"
                :disabled="newCategory.processing || !newCategory.name.trim()"
                class="rounded-md bg-gray-900 px-3 py-1.5 text-xs font-medium text-white hover:bg-gray-800 disabled:opacity-50"
                @click="addCategory"
            >
                Add Category
            </button>
        </div>
    </div>
</template>
