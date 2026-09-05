<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import { Head, router, useForm } from '@inertiajs/vue3'
import draggable from 'vuedraggable'
import AdminLayout from '@/Layouts/AdminLayout.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import Can from '@/Components/Shared/Can.vue'
import { useConfirm } from '@/Composables/useConfirm'
import { usePermissions } from '@/Composables/usePermissions'

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
    /** Admin trees keyed by location. Named `trees` (not `menus`) to avoid
     *  colliding with the shared public `menus` Inertia prop. */
    trees: Record<string, MenuTreeItem[]>
    pages: ContentPage[]
    posts: ContentPost[]
}

const props = defineProps<Props>()
const { confirm } = useConfirm()
const { can } = usePermissions()

const locationKeys = computed(() => Object.keys(props.locations))
const activeLocation = ref(locationKeys.value[0] ?? 'header')

const tree = ref<MenuTreeItem[]>([])
const saveStatus = ref<'idle' | 'saving' | 'saved' | 'error'>('idle')
const highlightedId = ref<number | null>(null)
const rejectShake = ref(false)
let savedTimer: ReturnType<typeof setTimeout> | null = null
let highlightTimer: ReturnType<typeof setTimeout> | null = null

/** Deep-clone plain JSON trees. Avoid structuredClone — Inertia/Vue props are
 *  Proxies and structuredClone throws "could not be cloned". */
function cloneTree(items: MenuTreeItem[]): MenuTreeItem[] {
    return JSON.parse(JSON.stringify(items)) as MenuTreeItem[]
}

watch(
    () => [props.trees, activeLocation.value] as const,
    () => {
        tree.value = cloneTree(props.trees[activeLocation.value] ?? [])
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
    saveStatus.value = 'idle'
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

const canReorder = computed(() => can('menus.update'))

const rootGroup = { name: 'menu-items', pull: true, put: true }
const childGroup = {
    name: 'menu-items',
    pull: true,
    // Nested under a root only — never accept a parent that already has children (depth-3).
    put: (_to: unknown, _from: unknown, dragEl: HTMLElement) => {
        const idAttr = dragEl.getAttribute('data-menu-id')
        const id = idAttr ? Number(idAttr) : NaN
        const item = findItem(tree.value, id)
        if (item && item.children.length > 0) {
            flashReject()
            return false
        }
        return true
    },
}

function findItem(list: MenuTreeItem[], id: number): MenuTreeItem | null {
    for (const item of list) {
        if (item.id === id) return item
        const nested = findItem(item.children, id)
        if (nested) return nested
    }
    return null
}

function itemType(item: MenuTreeItem): 'Page' | 'Post' | 'Custom' {
    if (props.pages.some((p) => p.url === item.url)) return 'Page'
    if (props.posts.some((p) => p.url === item.url) || item.url.startsWith('/blog/')) return 'Post'
    return 'Custom'
}

function typeBadgeClass(type: 'Page' | 'Post' | 'Custom'): string {
    if (type === 'Page') return 'bg-brand-600/15 text-brand-300'
    if (type === 'Post') return 'bg-emerald-500/15 text-emerald-400'
    return 'bg-gray-200 text-gray-600'
}

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

function flashHighlight(id: number) {
    highlightedId.value = id
    if (highlightTimer) clearTimeout(highlightTimer)
    highlightTimer = setTimeout(() => { highlightedId.value = null }, 900)
}

function flashReject() {
    rejectShake.value = true
    window.setTimeout(() => { rejectShake.value = false }, 450)
}

function persistOrder(movedId?: number) {
    const items = flattenTree(tree.value)
    if (!items.length) return
    saveStatus.value = 'saving'
    router.put('/admin/menus/reorder', { items }, {
        preserveScroll: true,
        onSuccess: () => {
            saveStatus.value = 'saved'
            if (movedId) flashHighlight(movedId)
            if (savedTimer) clearTimeout(savedTimer)
            savedTimer = setTimeout(() => { saveStatus.value = 'idle' }, 2000)
        },
        onError: () => { saveStatus.value = 'error' },
        onFinish: () => {
            if (saveStatus.value === 'saving') saveStatus.value = 'idle'
        },
    })
}

function onDragEnd(evt: { item?: HTMLElement }) {
    const idAttr = evt.item?.getAttribute('data-menu-id')
    const id = idAttr ? Number(idAttr) : undefined
    persistOrder(Number.isFinite(id) ? id : undefined)
}

/** Block nesting a parent-with-children under another parent (move prop). */
function onMove(evt: {
    draggedContext?: { element?: MenuTreeItem }
    relatedContext?: { list?: MenuTreeItem[] }
}): boolean {
    const dragged = evt.draggedContext?.element
    const relatedList = evt.relatedContext?.list
    if (!dragged || !relatedList) return true

    const droppingIntoChildList = tree.value.some((root) => root.children === relatedList)
    if (droppingIntoChildList && (dragged.children?.length ?? 0) > 0) {
        flashReject()
        return false
    }
    return true
}

function moveItem(list: MenuTreeItem[], index: number, dir: -1 | 1) {
    const next = index + dir
    if (next < 0 || next >= list.length) return
    const copy = [...list]
    const [row] = copy.splice(index, 1)
    if (!row) return
    copy.splice(next, 0, row)
    list.splice(0, list.length, ...copy)
    persistOrder(row.id)
}

function indentItem(rootIndex: number) {
    if (rootIndex <= 0) return
    const roots = tree.value
    const item = roots[rootIndex]
    if (!item || item.children.length > 0) {
        flashReject()
        return
    }
    const parent = roots[rootIndex - 1]
    if (!parent) return
    const next = [...roots]
    next.splice(rootIndex, 1)
    parent.children = [...parent.children, { ...item, parent_id: parent.id, children: [] }]
    tree.value = next
    persistOrder(item.id)
}

function outdentItem(parent: MenuTreeItem, childIndex: number, rootIndex: number) {
    const child = parent.children[childIndex]
    if (!child) return
    parent.children = parent.children.filter((_, i) => i !== childIndex)
    const roots = [...tree.value]
    roots.splice(rootIndex + 1, 0, { ...child, parent_id: null, children: [] })
    tree.value = roots
    persistOrder(child.id)
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

const iconBtn = 'inline-flex h-7 w-7 items-center justify-center rounded border border-gray-200 text-gray-500 hover:bg-gray-50 hover:text-gray-800 disabled:cursor-not-allowed disabled:opacity-30'
</script>

<template>
    <Head title="Menus" />
    <h1 class="mb-6 text-2xl font-bold text-gray-900">Menus</h1>

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
                    ? 'border-brand-500 text-brand-300'
                    : 'border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700'"
                @click="activeLocation = key"
            >
                {{ label }}
            </button>
        </nav>
    </div>

    <!-- Add panel first (WP-style left rail); stacks above structure on narrow viewports -->
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="order-1 space-y-4 lg:order-1">
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
                            class="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
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
                            class="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
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
                            class="w-full rounded-md bg-brand-600 px-4 py-2 text-sm font-medium text-white hover:bg-brand-700 disabled:opacity-50"
                            :disabled="customForm.processing"
                        >
                            Add custom link
                        </button>
                        <div v-if="Object.keys(customForm.errors).length" class="rounded-md border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-700">
                            <p v-for="(error, field) in customForm.errors" :key="field">{{ error }}</p>
                        </div>
                    </form>
                </div>
            </Can>
        </div>

        <div class="order-2 lg:col-span-2 lg:order-2">
            <div
                class="rounded-lg border border-gray-200 bg-white transition-transform"
                :class="{ 'animate-menu-shake border-rose-400': rejectShake }"
            >
                <div class="flex flex-wrap items-start justify-between gap-2 border-b border-gray-100 px-4 py-3">
                    <div>
                        <h2 class="text-sm font-semibold text-gray-900">
                            {{ locations[activeLocation] ?? activeLocation }} structure
                        </h2>
                        <p class="mt-0.5 text-xs text-gray-500">
                            Drag to reorder — drop under a parent to nest (max 2 levels).
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <span
                            v-if="saveStatus === 'saving'"
                            class="rounded-full bg-gray-100 px-2.5 py-1 text-[11px] font-medium text-gray-600"
                        >
                            Saving…
                        </span>
                        <span
                            v-else-if="saveStatus === 'saved'"
                            class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-[11px] font-medium text-emerald-600"
                        >
                            Saved ✓
                        </span>
                        <button
                            v-else-if="saveStatus === 'error'"
                            type="button"
                            class="rounded-full bg-rose-500/15 px-2.5 py-1 text-[11px] font-medium text-rose-600 hover:bg-rose-500/25"
                            @click="persistOrder()"
                        >
                            Save failed — retry
                        </button>
                    </div>
                </div>

                <div v-if="!tree.length" class="px-4 py-10 text-center text-sm text-gray-500">
                    No items yet. Use <span class="font-medium text-gray-700">Add to menu</span> on the left to add a page, post, or custom link.
                </div>

                <draggable
                    v-else
                    v-model="tree"
                    item-key="id"
                    handle=".menu-drag-handle"
                    tag="ul"
                    class="divide-y divide-gray-100"
                    :group="rootGroup"
                    :animation="180"
                    :disabled="!canReorder"
                    ghost-class="menu-ghost"
                    chosen-class="menu-chosen"
                    :move="onMove"
                    @end="onDragEnd"
                >
                    <template #item="{ element: item, index }">
                        <li
                            :data-menu-id="item.id"
                            class="px-3 py-2.5 transition-colors sm:px-4"
                            :class="{ 'bg-brand-600/10': highlightedId === item.id }"
                        >
                            <div class="flex items-start gap-2">
                                <button
                                    v-if="canReorder"
                                    type="button"
                                    class="menu-drag-handle mt-1 cursor-grab touch-none px-1 text-gray-400 hover:text-gray-700 active:cursor-grabbing"
                                    aria-label="Drag to reorder"
                                    title="Drag to reorder"
                                >
                                    ⠿
                                </button>

                                <div class="min-w-0 flex-1">
                                    <template v-if="editingId === item.id">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <AppInput v-model="editForm.title" class="min-w-[8rem] flex-1" placeholder="Label" />
                                            <AppInput v-model="editForm.url" class="min-w-[8rem] flex-1" placeholder="/path or https://…" />
                                            <label class="flex items-center gap-2 text-xs text-gray-600">
                                                <AppSwitch v-model="editForm.is_active" />
                                                Active
                                            </label>
                                            <button type="button" class="text-sm text-emerald-600 hover:text-emerald-800" @click="saveEdit(item.id)">Save</button>
                                            <button type="button" class="text-sm text-gray-500 hover:text-gray-700" @click="editingId = null">Cancel</button>
                                        </div>
                                        <div v-if="Object.keys(editForm.errors).length" class="mt-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs text-rose-700">
                                            <p v-for="(error, field) in editForm.errors" :key="field">{{ error }}</p>
                                        </div>
                                    </template>
                                    <template v-else>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="text-sm font-medium text-gray-900" :class="{ 'opacity-50': !item.is_active }">{{ item.title }}</span>
                                            <span
                                                class="rounded px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide"
                                                :class="typeBadgeClass(itemType(item))"
                                            >{{ itemType(item) }}</span>
                                            <span v-if="item.children.length" class="text-[10px] text-gray-400">
                                                {{ item.children.length }} child{{ item.children.length === 1 ? '' : 'ren' }}
                                            </span>
                                            <span v-if="!item.is_active" class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-500">Hidden</span>
                                        </div>
                                        <p class="mt-0.5 truncate text-xs text-gray-500">{{ item.url }}</p>
                                        <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                            <Can permission="menus.update">
                                                <button type="button" :class="iconBtn" aria-label="Move up" title="Move up" :disabled="index === 0" @click="moveItem(tree, index, -1)">↑</button>
                                                <button type="button" :class="iconBtn" aria-label="Move down" title="Move down" :disabled="index >= tree.length - 1" @click="moveItem(tree, index, 1)">↓</button>
                                                <button
                                                    type="button"
                                                    :class="iconBtn"
                                                    aria-label="Nest under previous"
                                                    title="Nest under previous"
                                                    :disabled="index === 0 || item.children.length > 0"
                                                    @click="indentItem(index)"
                                                >→</button>
                                                <button type="button" class="ml-1 text-xs text-gray-600 hover:text-gray-900" @click="startEdit(item)">Edit</button>
                                            </Can>
                                            <Can permission="menus.delete">
                                                <button type="button" class="text-xs text-rose-600 hover:text-rose-800" @click="deleteMenu(item.id)">Delete</button>
                                            </Can>
                                        </div>
                                    </template>

                                    <draggable
                                        v-model="item.children"
                                        item-key="id"
                                        handle=".menu-drag-handle"
                                        tag="ul"
                                        class="mt-2 min-h-[0.5rem] space-y-1 border-l-2 border-gray-200 pl-3"
                                        :group="childGroup"
                                        :animation="180"
                                        :disabled="!canReorder"
                                        ghost-class="menu-ghost"
                                        chosen-class="menu-chosen"
                                        :move="onMove"
                                        @end="onDragEnd"
                                    >
                                        <template #item="{ element: child, index: cIndex }">
                                            <li
                                                :data-menu-id="child.id"
                                                class="rounded-md py-1.5 transition-colors"
                                                :class="{ 'bg-brand-600/10': highlightedId === child.id }"
                                            >
                                                <div class="flex items-start gap-2">
                                                    <button
                                                        v-if="canReorder"
                                                        type="button"
                                                        class="menu-drag-handle mt-0.5 cursor-grab touch-none px-1 text-gray-400 hover:text-gray-700 active:cursor-grabbing"
                                                        aria-label="Drag to reorder"
                                                        title="Drag to reorder"
                                                    >
                                                        ⠿
                                                    </button>
                                                    <div class="min-w-0 flex-1">
                                                        <template v-if="editingId === child.id">
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <AppInput v-model="editForm.title" class="min-w-[8rem] flex-1" placeholder="Label" />
                                                                <AppInput v-model="editForm.url" class="min-w-[8rem] flex-1" placeholder="/path or https://…" />
                                                                <label class="flex items-center gap-2 text-xs text-gray-600">
                                                                    <AppSwitch v-model="editForm.is_active" />
                                                                    Active
                                                                </label>
                                                                <button type="button" class="text-sm text-emerald-600 hover:text-emerald-800" @click="saveEdit(child.id)">Save</button>
                                                                <button type="button" class="text-sm text-gray-500" @click="editingId = null">Cancel</button>
                                                            </div>
                                                            <div v-if="Object.keys(editForm.errors).length" class="mt-2 rounded-md border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs text-rose-700">
                                                                <p v-for="(error, field) in editForm.errors" :key="field">{{ error }}</p>
                                                            </div>
                                                        </template>
                                                        <template v-else>
                                                            <div class="flex flex-wrap items-center gap-2">
                                                                <span class="text-sm text-gray-900" :class="{ 'opacity-50': !child.is_active }">{{ child.title }}</span>
                                                                <span
                                                                    class="rounded px-1.5 py-0.5 text-[10px] font-medium uppercase tracking-wide"
                                                                    :class="typeBadgeClass(itemType(child))"
                                                                >{{ itemType(child) }}</span>
                                                                <span v-if="!child.is_active" class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-500">Hidden</span>
                                                            </div>
                                                            <p class="mt-0.5 truncate text-xs text-gray-500">{{ child.url }}</p>
                                                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                                <Can permission="menus.update">
                                                                    <button type="button" :class="iconBtn" aria-label="Move up" title="Move up" :disabled="cIndex === 0" @click="moveItem(item.children, cIndex, -1)">↑</button>
                                                                    <button type="button" :class="iconBtn" aria-label="Move down" title="Move down" :disabled="cIndex >= item.children.length - 1" @click="moveItem(item.children, cIndex, 1)">↓</button>
                                                                    <button type="button" :class="iconBtn" aria-label="Outdent to top level" title="Outdent to top level" @click="outdentItem(item, cIndex, index)">←</button>
                                                                    <button type="button" class="ml-1 text-xs text-gray-600 hover:text-gray-900" @click="startEdit(child)">Edit</button>
                                                                </Can>
                                                                <Can permission="menus.delete">
                                                                    <button type="button" class="text-xs text-rose-600 hover:text-rose-800" @click="deleteMenu(child.id)">Delete</button>
                                                                </Can>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            </li>
                                        </template>
                                    </draggable>
                                </div>
                            </div>
                        </li>
                    </template>
                </draggable>
            </div>
        </div>
    </div>
</template>

<style scoped>
.menu-ghost {
    opacity: 0.45;
    background: color-mix(in srgb, var(--color-brand-600, #4f46e5) 12%, transparent);
}
.menu-chosen {
    background: color-mix(in srgb, var(--color-brand-600, #4f46e5) 8%, transparent);
}
@keyframes menu-shake {
    0%, 100% { transform: translateX(0); }
    20% { transform: translateX(-4px); }
    40% { transform: translateX(4px); }
    60% { transform: translateX(-3px); }
    80% { transform: translateX(3px); }
}
.animate-menu-shake {
    animation: menu-shake 0.4s ease;
}
</style>
