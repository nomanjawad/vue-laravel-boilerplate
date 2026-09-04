<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import Can from '@/Components/Shared/Can.vue'
import { useConfirm } from '@/Composables/useConfirm'

defineOptions({ layout: AdminLayout })

interface MenuTreeItem {
    id: number
    title: string
    url: string
    parent_id: number | null
    sort_order: number
    is_active: boolean
    children: MenuTreeItem[]
}

interface ContentPage {
    slug: string
    title: string
    url: string
}

interface ContentPost {
    id: number
    title: string
    url: string
}

interface Props {
    locations: Record<string, string>
    menus: Record<string, MenuTreeItem[]>
    pages: ContentPage[]
    posts: ContentPost[]
}

const props = defineProps<Props>()
const { confirm } = useConfirm()

const locationKeys = computed(() => Object.keys(props.locations))
const activeLocation = ref(locationKeys.value[0] ?? 'header')

const tree = ref<MenuTreeItem[]>([])

watch(
    () => [props.menus, activeLocation.value] as const,
    () => {
        tree.value = structuredClone(props.menus[activeLocation.value] ?? [])
    },
    { immediate: true, deep: true },
)

const editingId = ref<number | null>(null)
const editForm = useForm({
    title: '',
    url: '',
    is_active: true,
})

const customForm = useForm({
    location: activeLocation.value,
    title: '',
    url: '',
    parent_id: null as number | null,
    sort_order: 0,
    is_active: true,
})

watch(activeLocation, (loc) => {
    customForm.location = loc
    editingId.value = null
})

const addSource = ref<'page' | 'post' | 'custom'>('page')
const selectedPageSlug = ref('')
const selectedPostId = ref('')

const pageOptions = computed(() =>
    props.pages.map((p) => ({ value: p.slug, label: `${p.title} (${p.url})` })),
)
const postOptions = computed(() =>
    props.posts.map((p) => ({ value: String(p.id), label: p.title })),
)

function startEdit(item: MenuTreeItem) {
    editingId.value = item.id
    editForm.clearErrors()
    editForm.title = item.title
    editForm.url = item.url
    editForm.is_active = item.is_active
}

function saveEdit(id: number) {
    editForm.put(`/admin/menus/${id}`, {
        preserveScroll: true,
        onSuccess: () => { editingId.value = null },
    })
}

async function deleteMenu(id: number) {
    const ok = await confirm({
        title: 'Delete this menu item?',
        body: 'Child items under this entry will also be removed.',
        confirmLabel: 'Delete',
        confirmTone: 'danger',
    })
    if (!ok) return
    router.delete(`/admin/menus/${id}`, { preserveScroll: true })
}

function flattenTree(items: MenuTreeItem[], parentId: number | null = null): Array<{ id: number; parent_id: number | null; sort_order: number }> {
    const out: Array<{ id: number; parent_id: number | null; sort_order: number }> = []
    items.forEach((item, index) => {
        out.push({ id: item.id, parent_id: parentId, sort_order: index })
        if (item.children?.length) {
            out.push(...flattenTree(item.children, item.id))
        }
    })
    return out
}

function persistOrder() {
    const items = flattenTree(tree.value)
    if (!items.length) return
    router.put('/admin/menus/reorder', { items }, { preserveScroll: true })
}

function moveItem(list: MenuTreeItem[], index: number, dir: -1 | 1) {
    const next = index + dir
    if (next < 0 || next >= list.length) return
    const copy = [...list]
    const [row] = copy.splice(index, 1)
    if (!row) return
    copy.splice(next, 0, row)
    list.splice(0, list.length, ...copy)
    persistOrder()
}

function indentItem(rootIndex: number) {
    if (rootIndex <= 0) return
    const roots = tree.value
    const item = roots[rootIndex]
    if (!item || item.children.length > 0) return
    const parent = roots[rootIndex - 1]
    if (!parent) return
    const next = [...roots]
    next.splice(rootIndex, 1)
    parent.children = [...parent.children, { ...item, parent_id: parent.id, children: [] }]
    tree.value = next
    persistOrder()
}

function outdentItem(parent: MenuTreeItem, childIndex: number, rootIndex: number) {
    const child = parent.children[childIndex]
    if (!child) return
    parent.children = parent.children.filter((_, i) => i !== childIndex)
    const roots = [...tree.value]
    roots.splice(rootIndex + 1, 0, { ...child, parent_id: null, children: [] })
    tree.value = roots
    persistOrder()
}

function insertLink(title: string, url: string) {
    customForm.location = activeLocation.value
    customForm.title = title
    customForm.url = url
    customForm.parent_id = null
    customForm.sort_order = tree.value.length
    customForm.is_active = true
    customForm.post('/admin/menus', {
        preserveScroll: true,
        onSuccess: () => {
            customForm.reset('title', 'url')
            selectedPageSlug.value = ''
            selectedPostId.value = ''
        },
    })
}

function addFromPage() {
    const page = props.pages.find((p) => p.slug === selectedPageSlug.value)
    if (!page) return
    insertLink(page.title, page.url)
}

function addFromPost() {
    const id = Number(selectedPostId.value)
    const post = props.posts.find((p) => p.id === id)
    if (!post) return
    insertLink(post.title, post.url)
}

function addCustom() {
    customForm.location = activeLocation.value
    customForm.parent_id = null
    customForm.sort_order = tree.value.length
    customForm.post('/admin/menus', {
        preserveScroll: true,
        onSuccess: () => customForm.reset('title', 'url'),
    })
}
</script>

<template>
    <Head title="Menus" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Menus</h1>

    <!-- Location tabs -->
    <div class="mb-6 border-b border-gray-200">
        <nav class="-mb-px flex gap-6 overflow-x-auto" role="tablist" aria-label="Menu locations">
            <button
                v-for="(label, key) in locations"
                :key="key"
                type="button"
                role="tab"
                :aria-selected="activeLocation === key"
                class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors"
                :class="activeLocation === key
                    ? 'border-indigo-600 text-indigo-600'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                @click="activeLocation = key"
            >
                {{ label }}
            </button>
        </nav>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <!-- Nested tree -->
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-gray-200 bg-white">
                <div class="border-b border-gray-100 px-4 py-3">
                    <h2 class="text-sm font-semibold text-gray-900">
                        {{ locations[activeLocation] ?? activeLocation }} structure
                    </h2>
                    <p class="mt-0.5 text-xs text-gray-500">
                        Nesting capped at 2 levels. Use ↑↓ to reorder, →← to indent/outdent.
                    </p>
                </div>

                <div v-if="!tree.length" class="px-4 py-8 text-center text-sm text-gray-500">
                    No items yet. Add a page, post, or custom link from the panel.
                </div>

                <ul class="divide-y divide-gray-100">
                    <li v-for="(item, index) in tree" :key="item.id" class="px-4 py-3">
                        <div class="flex items-start gap-2">
                            <div class="flex shrink-0 flex-col gap-0.5 pt-0.5">
                                <Can permission="menus.update">
                                    <button type="button" class="rounded px-1.5 text-xs text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Move up" @click="moveItem(tree, index, -1)">↑</button>
                                    <button type="button" class="rounded px-1.5 text-xs text-gray-400 hover:bg-gray-100 hover:text-gray-700" title="Move down" @click="moveItem(tree, index, 1)">↓</button>
                                    <button
                                        type="button"
                                        class="rounded px-1.5 text-xs text-gray-400 hover:bg-gray-100 hover:text-gray-700 disabled:opacity-30"
                                        title="Indent under previous"
                                        :disabled="index === 0 || item.children.length > 0"
                                        @click="indentItem(index)"
                                    >→</button>
                                </Can>
                            </div>

                            <div class="min-w-0 flex-1">
                                <template v-if="editingId === item.id">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <AppInput v-model="editForm.title" class="min-w-[8rem] flex-1" placeholder="Label" />
                                        <AppInput v-model="editForm.url" class="min-w-[8rem] flex-1" placeholder="/path or https://…" />
                                        <label class="flex items-center gap-2 text-xs text-gray-600">
                                            <AppSwitch v-model="editForm.is_active" />
                                            Active
                                        </label>
                                        <button type="button" class="text-sm text-green-600 hover:text-green-800" @click="saveEdit(item.id)">Save</button>
                                        <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="editingId = null">Cancel</button>
                                    </div>
                                    <div v-if="Object.keys(editForm.errors).length" class="mt-2 rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs text-red-700">
                                        <p v-for="(error, field) in editForm.errors" :key="field">{{ error }}</p>
                                    </div>
                                </template>
                                <template v-else>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-sm font-medium text-gray-900" :class="{ 'opacity-50': !item.is_active }">{{ item.title }}</span>
                                        <span class="truncate text-xs text-gray-500">{{ item.url }}</span>
                                        <span v-if="!item.is_active" class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-500">Hidden</span>
                                        <Can permission="menus.update">
                                            <button type="button" class="text-xs text-gray-600 hover:text-gray-900" @click="startEdit(item)">Edit</button>
                                        </Can>
                                        <Can permission="menus.delete">
                                            <button type="button" class="text-xs text-red-600 hover:text-red-800" @click="deleteMenu(item.id)">Delete</button>
                                        </Can>
                                    </div>
                                </template>

                                <ul v-if="item.children.length" class="mt-2 ml-4 space-y-2 border-l border-gray-200 pl-3">
                                    <li v-for="(child, cIndex) in item.children" :key="child.id" class="flex items-start gap-2">
                                        <div class="flex shrink-0 flex-col gap-0.5 pt-0.5">
                                            <Can permission="menus.update">
                                                <button type="button" class="rounded px-1.5 text-xs text-gray-400 hover:bg-gray-100" title="Move up" @click="moveItem(item.children, cIndex, -1)">↑</button>
                                                <button type="button" class="rounded px-1.5 text-xs text-gray-400 hover:bg-gray-100" title="Move down" @click="moveItem(item.children, cIndex, 1)">↓</button>
                                                <button type="button" class="rounded px-1.5 text-xs text-gray-400 hover:bg-gray-100" title="Outdent to root" @click="outdentItem(item, cIndex, index)">←</button>
                                            </Can>
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <template v-if="editingId === child.id">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <AppInput v-model="editForm.title" class="min-w-[8rem] flex-1" placeholder="Label" />
                                                    <AppInput v-model="editForm.url" class="min-w-[8rem] flex-1" placeholder="/path or https://…" />
                                                    <label class="flex items-center gap-2 text-xs text-gray-600">
                                                        <AppSwitch v-model="editForm.is_active" />
                                                        Active
                                                    </label>
                                                    <button type="button" class="text-sm text-green-600 hover:text-green-800" @click="saveEdit(child.id)">Save</button>
                                                    <button type="button" class="text-sm text-gray-500" @click="editingId = null">Cancel</button>
                                                </div>
                                                <div v-if="Object.keys(editForm.errors).length" class="mt-2 rounded-md border border-red-200 bg-red-50 px-3 py-1.5 text-xs text-red-700">
                                                    <p v-for="(error, field) in editForm.errors" :key="field">{{ error }}</p>
                                                </div>
                                            </template>
                                            <template v-else>
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-sm text-gray-900" :class="{ 'opacity-50': !child.is_active }">{{ child.title }}</span>
                                                    <span class="truncate text-xs text-gray-500">{{ child.url }}</span>
                                                    <span v-if="!child.is_active" class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-500">Hidden</span>
                                                    <Can permission="menus.update">
                                                        <button type="button" class="text-xs text-gray-600 hover:text-gray-900" @click="startEdit(child)">Edit</button>
                                                    </Can>
                                                    <Can permission="menus.delete">
                                                        <button type="button" class="text-xs text-red-600 hover:text-red-800" @click="deleteMenu(child.id)">Delete</button>
                                                    </Can>
                                                </div>
                                            </template>
                                        </div>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Add from content -->
        <div class="space-y-4">
            <Can permission="menus.create">
                <div class="rounded-lg border border-gray-200 bg-white p-4">
                    <h2 class="mb-3 text-sm font-semibold text-gray-900">Add to menu</h2>

                    <div class="mb-3 flex gap-1 rounded-md bg-gray-100 p-0.5">
                        <button
                            v-for="tab in (['page', 'post', 'custom'] as const)"
                            :key="tab"
                            type="button"
                            class="flex-1 rounded px-2 py-1.5 text-xs font-medium capitalize"
                            :class="addSource === tab ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-600'"
                            @click="addSource = tab"
                        >
                            {{ tab === 'page' ? 'Pages' : tab === 'post' ? 'Posts' : 'Custom' }}
                        </button>
                    </div>

                    <div v-if="addSource === 'page'" class="space-y-3">
                        <AppFormField name="page" label="Page">
                            <AppSelect v-model="selectedPageSlug" :options="pageOptions" placeholder="Select a page…" />
                        </AppFormField>
                        <button
                            type="button"
                            class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="!selectedPageSlug || customForm.processing"
                            @click="addFromPage"
                        >
                            Add page
                        </button>
                    </div>

                    <div v-else-if="addSource === 'post'" class="space-y-3">
                        <AppFormField name="post" label="Post">
                            <AppSelect v-model="selectedPostId" :options="postOptions" placeholder="Select a post…" />
                        </AppFormField>
                        <button
                            type="button"
                            class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="!selectedPostId || customForm.processing"
                            @click="addFromPost"
                        >
                            Add post
                        </button>
                    </div>

                    <form v-else class="space-y-3" @submit.prevent="addCustom">
                        <AppFormField name="title" label="Label">
                            <AppInput v-model="customForm.title" required placeholder="About us" />
                        </AppFormField>
                        <AppFormField name="url" label="URL">
                            <AppInput v-model="customForm.url" required placeholder="/about or https://…" />
                        </AppFormField>
                        <button
                            type="submit"
                            class="rounded bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:opacity-50"
                            :disabled="customForm.processing"
                        >
                            Add custom link
                        </button>
                        <div v-if="Object.keys(customForm.errors).length" class="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-xs text-red-700">
                            <p v-for="(error, field) in customForm.errors" :key="field">{{ error }}</p>
                        </div>
                    </form>
                </div>
            </Can>
        </div>
    </div>
</template>
