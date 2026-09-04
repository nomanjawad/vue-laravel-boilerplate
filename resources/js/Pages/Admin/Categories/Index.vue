<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { computed, ref, watch } from 'vue'
import BlogTabs from '@/Components/Organisms/BlogTabs.vue'

defineOptions({ layout: AdminLayout })

interface CategoryRow {
    id: number
    name: string
    slug: string
    description?: string | null
    parent_id?: number | null
    sort_order?: number
    posts_count: number
}

interface Props {
    categories: CategoryRow[]
}

const props = defineProps<Props>()

interface FlatCategory extends CategoryRow {
    depth: number
}

function flattenTree(cats: CategoryRow[], parentId: number | null = null, depth = 0): FlatCategory[] {
    return cats
        .filter((c) => (c.parent_id ?? null) === parentId)
        .flatMap((c) => [{ ...c, depth }, ...flattenTree(cats, c.id, depth + 1)])
}

const flatCategories = computed(() => flattenTree(props.categories))

const newForm = useForm({
    name: '',
    slug: '',
    description: '',
    parent_id: '' as number | '',
    sort_order: 0,
})

function slugify(value: string): string {
    return value
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '')
}

const slugTouched = ref(false)

watch(
    () => newForm.name,
    (name) => {
        if (!slugTouched.value) {
            newForm.slug = slugify(name)
        }
    },
)

function addCategory() {
    newForm.post('/admin/categories', {
        preserveScroll: true,
        onSuccess: () => {
            newForm.reset()
            slugTouched.value = false
        },
    })
}

const editingId = ref<number | null>(null)
const editForm = useForm({
    name: '',
    slug: '',
    description: '',
    parent_id: '' as number | '',
    sort_order: 0,
})

const editingCategory = computed(() => props.categories.find((c) => c.id === editingId.value) ?? null)

function startEdit(cat: CategoryRow) {
    editingId.value = cat.id
    editForm.name = cat.name
    editForm.slug = cat.slug
    editForm.description = cat.description || ''
    editForm.parent_id = cat.parent_id ?? ''
    editForm.sort_order = cat.sort_order ?? 0
    editForm.clearErrors()
}

function closeEdit() {
    editingId.value = null
}

function saveEdit() {
    if (!editingId.value) return
    editForm.put(`/admin/categories/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null
        },
    })
}

function deleteCategory(cat: CategoryRow) {
    if (cat.slug === 'uncategorized') return
    if (confirm(`Delete "${cat.name}"? Children will be moved up one level.`)) {
        router.delete(`/admin/categories/${cat.id}`, { preserveScroll: true })
    }
}

const parentOptionsForEdit = computed(() => {
    if (!editingId.value) return flatCategories.value
    const blocked = new Set<number>([editingId.value])
    const descendants = (id: number) => {
        props.categories.filter((c) => c.parent_id === id).forEach((c) => {
            blocked.add(c.id)
            descendants(c.id)
        })
    }
    descendants(editingId.value)
    return flatCategories.value.filter((c) => !blocked.has(c.id))
})
</script>

<template>
    <Head title="Categories" />
    <BlogTabs />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Categories</h1>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <!-- Add form (left) -->
        <div class="bg-white rounded-lg shadow p-6 lg:col-span-1 h-fit">
            <h2 class="mb-4 text-lg font-semibold text-gray-900">Add Category</h2>
            <form class="space-y-4" @submit.prevent="addCategory">
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                    <input
                        v-model="newForm.name"
                        type="text"
                        required
                        placeholder="Category name"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                    />
                    <p v-if="newForm.errors.name" class="mt-1 text-sm text-red-600">{{ newForm.errors.name }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Slug</label>
                    <input
                        v-model="newForm.slug"
                        type="text"
                        placeholder="category-slug"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        @input="slugTouched = true"
                    />
                    <p v-if="newForm.errors.slug" class="mt-1 text-sm text-red-600">{{ newForm.errors.slug }}</p>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Parent</label>
                    <select
                        v-model="newForm.parent_id"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                    >
                        <option value="">— None —</option>
                        <option v-for="cat in flatCategories" :key="cat.id" :value="cat.id">
                            {{ '— '.repeat(cat.depth) }}{{ cat.name }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium text-gray-500">Description</label>
                    <textarea
                        v-model="newForm.description"
                        rows="3"
                        placeholder="Optional description"
                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                    />
                </div>
                <button
                    type="submit"
                    :disabled="newForm.processing"
                    class="w-full rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
                >
                    Add Category
                </button>
            </form>
        </div>

        <!-- Hierarchy table (right) -->
        <div class="bg-white rounded-lg shadow lg:col-span-2 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Slug</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase text-gray-500">Posts</th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase text-gray-500">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <tr v-for="cat in flatCategories" :key="cat.id">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <span :style="{ paddingLeft: `${cat.depth * 1.25}rem` }">{{ cat.name }}</span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ cat.slug }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ cat.posts_count }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <button class="mr-3 text-gray-600 hover:text-gray-900" @click="startEdit(cat)">Edit</button>
                                <button
                                    v-if="cat.slug !== 'uncategorized'"
                                    class="text-red-600 hover:text-red-900"
                                    @click="deleteCategory(cat)"
                                >
                                    Delete
                                </button>
                                <span v-else class="text-xs text-gray-400">Default</span>
                            </td>
                        </tr>
                        <tr v-if="!flatCategories.length">
                            <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-500">No categories yet.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Edit slide-over -->
    <Teleport to="body">
        <div v-if="editingId" class="fixed inset-0 z-40 flex justify-end">
            <div class="absolute inset-0 bg-black/30" @click="closeEdit" />
            <div class="relative z-10 flex h-full w-full max-w-md flex-col bg-white shadow-xl">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Category</h3>
                    <button type="button" class="text-gray-400 hover:text-gray-600" @click="closeEdit">&times;</button>
                </div>
                <form class="flex-1 space-y-4 overflow-y-auto px-6 py-4" @submit.prevent="saveEdit">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Name</label>
                        <input
                            v-model="editForm.name"
                            type="text"
                            required
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        />
                        <p v-if="editForm.errors.name" class="mt-1 text-sm text-red-600">{{ editForm.errors.name }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Slug</label>
                        <input
                            v-model="editForm.slug"
                            type="text"
                            :disabled="editingCategory?.slug === 'uncategorized'"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500 disabled:bg-gray-100"
                        />
                        <p v-if="editForm.errors.slug" class="mt-1 text-sm text-red-600">{{ editForm.errors.slug }}</p>
                    </div>
                    <div v-if="editingCategory?.slug !== 'uncategorized'">
                        <label class="mb-1 block text-xs font-medium text-gray-500">Parent</label>
                        <select
                            v-model="editForm.parent_id"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        >
                            <option value="">— None —</option>
                            <option v-for="cat in parentOptionsForEdit" :key="cat.id" :value="cat.id">
                                {{ '— '.repeat(cat.depth) }}{{ cat.name }}
                            </option>
                        </select>
                        <p v-if="editForm.errors.parent_id" class="mt-1 text-sm text-red-600">{{ editForm.errors.parent_id }}</p>
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-gray-500">Description</label>
                        <textarea
                            v-model="editForm.description"
                            rows="4"
                            class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500"
                        />
                    </div>
                    <div class="flex gap-3 pt-2">
                        <button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800 disabled:opacity-50"
                        >
                            Save
                        </button>
                        <button type="button" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900" @click="closeEdit">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>
