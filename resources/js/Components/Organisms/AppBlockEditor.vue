<script setup lang="ts">
/**
 * Gutenberg-like block editor on TipTap. Output is HTML (getHTML) so
 * Public/Blog/Show.vue v-html and ImportWordPress keep working.
 *
 * Slash menu (/) inserts blocks; floating toolbar for marks; image opens
 * the media library browser. Doc import (.docx/.md) + rich paste route
 * tables/images through the media library.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue'
import { EditorContent, useEditor } from '@tiptap/vue-3'
import StarterKit from '@tiptap/starter-kit'
import Link from '@tiptap/extension-link'
import Image from '@tiptap/extension-image'
import Underline from '@tiptap/extension-underline'
import TextAlign from '@tiptap/extension-text-align'
import Placeholder from '@tiptap/extension-placeholder'
import { Table } from '@tiptap/extension-table'
import { TableRow } from '@tiptap/extension-table-row'
import { TableCell } from '@tiptap/extension-table-cell'
import { TableHeader } from '@tiptap/extension-table-header'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'
import { ingestEditorHtml } from '@/Utils/ingestEditorHtml'
import { importDocumentFile } from '@/Utils/importDocumentFile'

interface Props {
    modelValue?: string
    placeholder?: string
    /** Slimmer toolbar for widget richtext fields. */
    compact?: boolean
}

const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    placeholder: 'Type / for blocks…',
    compact: false,
})

const emit = defineEmits<{
    (e: 'update:modelValue', value: string): void
}>()

const slashOpen = ref(false)
const slashFilter = ref('')
const imagePickerOpen = ref(false)
const importing = ref(false)
const importError = ref<string | null>(null)
const importInputRef = ref<HTMLInputElement | null>(null)
const busy = ref(false)

const editor = useEditor({
    content: props.modelValue || '',
    extensions: [
        StarterKit.configure({
            heading: { levels: [2, 3] },
        }),
        Underline,
        TextAlign.configure({
            types: ['heading', 'paragraph'],
        }),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        Link.configure({
            openOnClick: false,
            HTMLAttributes: { class: 'text-brand-600 underline' },
        }),
        Image.configure({
            HTMLAttributes: { class: 'rounded-lg max-w-full h-auto' },
        }),
        Table.configure({
            resizable: false,
            HTMLAttributes: { class: 'cms-table' },
        }),
        TableRow,
        TableHeader,
        TableCell,
    ],
    editorProps: {
        attributes: {
            class: 'prose prose-sm max-w-none min-h-[12rem] focus:outline-none px-3 py-2',
        },
        handleKeyDown: (view, event) => {
            if (event.key === '/' && !event.metaKey && !event.ctrlKey && !event.altKey) {
                const { $from } = view.state.selection
                // F11 #31 — only open slash menu at the start of an empty block
                // (avoids hijacking "24/7", URLs, dates).
                const emptyBlock = $from.parent.isTextblock
                    && $from.parentOffset === 0
                    && $from.parent.content.size === 0
                if (emptyBlock) {
                    setTimeout(() => {
                        slashOpen.value = true
                        slashFilter.value = ''
                    }, 0)
                }
            }
            if (event.key === 'Escape' && slashOpen.value) {
                slashOpen.value = false
                return true
            }
            return false
        },
        handlePaste: (_view, event) => {
            const html = event.clipboardData?.getData('text/html')
            if (!html || !editor.value) return false

            event.preventDefault()
            busy.value = true
            importError.value = null
            void (async () => {
                try {
                    const ingested = await ingestEditorHtml(html)
                    editor.value?.chain().focus().insertContent(ingested).run()
                } catch (e) {
                    importError.value = e instanceof Error ? e.message : 'Paste failed'
                } finally {
                    busy.value = false
                }
            })()
            return true
        },
    },
    onUpdate: ({ editor: ed }) => {
        emit('update:modelValue', ed.getHTML())
    },
})

watch(() => props.modelValue, (val) => {
    if (!editor.value) return
    const current = editor.value.getHTML()
    if ((val || '') !== current) {
        editor.value.commands.setContent(val || '', { emitUpdate: false })
    }
})

onBeforeUnmount(() => {
    editor.value?.destroy()
})

type BlockCmd = { key: string; label: string; run: () => void }

const blockCommands = computed<BlockCmd[]>(() => {
    const ed = editor.value
    if (!ed) return []
    const cmds: BlockCmd[] = [
        { key: 'paragraph', label: 'Paragraph', run: () => ed.chain().focus().setParagraph().run() },
        { key: 'h2', label: 'Heading 2', run: () => ed.chain().focus().toggleHeading({ level: 2 }).run() },
        { key: 'h3', label: 'Heading 3', run: () => ed.chain().focus().toggleHeading({ level: 3 }).run() },
        { key: 'bullet', label: 'Bulleted list', run: () => ed.chain().focus().toggleBulletList().run() },
        { key: 'ordered', label: 'Numbered list', run: () => ed.chain().focus().toggleOrderedList().run() },
        { key: 'quote', label: 'Quote', run: () => ed.chain().focus().toggleBlockquote().run() },
        { key: 'hr', label: 'Divider', run: () => ed.chain().focus().setHorizontalRule().run() },
        {
            key: 'table',
            label: 'Table',
            run: () => ed.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run(),
        },
        {
            key: 'image',
            label: 'Image',
            run: () => { imagePickerOpen.value = true },
        },
        {
            key: 'html',
            label: 'HTML block',
            run: () => ed.chain().focus().insertContent('<pre><code><!-- HTML --></code></pre>').run(),
        },
    ]
    const q = slashFilter.value.trim().toLowerCase()
    return q ? cmds.filter((c) => c.label.toLowerCase().includes(q)) : cmds
})

function runCommand(cmd: BlockCmd) {
    // Remove the leading "/" that opened the menu.
    const ed = editor.value
    if (ed) {
        const { from } = ed.state.selection
        const textBefore = ed.state.doc.textBetween(Math.max(0, from - 20), from, '\n', '\0')
        const slashIdx = textBefore.lastIndexOf('/')
        if (slashIdx >= 0) {
            const deleteFrom = from - (textBefore.length - slashIdx)
            ed.chain().focus().deleteRange({ from: deleteFrom, to: from }).run()
        }
    }
    slashOpen.value = false
    cmd.run()
}

function setLink() {
    const ed = editor.value
    if (!ed) return
    const prev = ed.getAttributes('link').href as string | undefined
    const url = window.prompt('Link URL', prev || 'https://')
    if (url === null) return
    if (url === '') {
        ed.chain().focus().extendMarkRange('link').unsetLink().run()
        return
    }
    ed.chain().focus().extendMarkRange('link').setLink({ href: url }).run()
}

type PickerMedia = { id: number | string; url?: string | null; alt_text?: string | null }

function onImagePicked(media: PickerMedia | null) {
    imagePickerOpen.value = false
    if (!media?.url || !editor.value) return
    editor.value.chain().focus().setImage({
        src: media.url,
        alt: media.alt_text ?? '',
    }).run()
}

function editorIsEmpty(): boolean {
    const ed = editor.value
    if (!ed) return true
    return ed.isEmpty || ed.getText().trim() === ''
}

function openImportPicker() {
    importError.value = null
    importInputRef.value?.click()
}

async function onImportFile(e: Event) {
    const input = e.target as HTMLInputElement
    const file = input.files?.[0]
    input.value = ''
    if (!file || !editor.value) return

    let mode: 'replace' | 'append' = 'replace'
    if (!editorIsEmpty()) {
        const choice = window.prompt(
            'Editor already has content.\nType "replace" to replace it, or "append" to add below.\n(Cancel to abort)\n\nTip: Google Docs → File → Download → Microsoft Word (.docx)',
            'replace',
        )
        if (choice === null) return
        const normalized = choice.trim().toLowerCase()
        if (normalized === 'append') mode = 'append'
        else if (normalized === 'replace') mode = 'replace'
        else {
            importError.value = 'Import cancelled — type replace or append.'
            return
        }
    }

    importing.value = true
    busy.value = true
    importError.value = null
    try {
        const html = await importDocumentFile(file)
        if (mode === 'replace') {
            editor.value.commands.setContent(html)
        } else {
            editor.value.chain().focus('end').insertContent(html).run()
        }
        emit('update:modelValue', editor.value.getHTML())
    } catch (err) {
        importError.value = err instanceof Error ? err.message : 'Import failed'
    } finally {
        importing.value = false
        busy.value = false
    }
}

const inTable = computed(() => editor.value?.isActive('table') ?? false)

const btn = (active: boolean) => [
    'rounded px-2 py-1 text-xs font-medium transition',
    active ? 'bg-gray-900 text-white' : 'text-gray-600 hover:bg-gray-100',
]
</script>

<template>
    <div class="relative rounded-lg border border-gray-300 bg-white">
        <div
            v-if="editor"
            class="flex flex-wrap items-center gap-1 border-b border-gray-200 px-2 py-1.5"
        >
            <button type="button" :class="btn(editor.isActive('bold'))" @click="editor.chain().focus().toggleBold().run()">B</button>
            <button type="button" :class="btn(editor.isActive('italic'))" @click="editor.chain().focus().toggleItalic().run()">I</button>
            <button type="button" :class="btn(editor.isActive('underline'))" @click="editor.chain().focus().toggleUnderline().run()">U</button>
            <button type="button" :class="btn(editor.isActive('link'))" @click="setLink">Link</button>
            <button
                type="button"
                :class="btn(false)"
                :disabled="importing || busy"
                title="Import .docx or .md"
                @click="openImportPicker"
            >
                {{ importing ? 'Importing…' : 'Import' }}
            </button>
            <input
                ref="importInputRef"
                type="file"
                accept=".docx,.md,.markdown,application/vnd.openxmlformats-officedocument.wordprocessingml.document,text/markdown,text/plain"
                class="sr-only"
                @change="onImportFile"
            >
            <template v-if="!compact">
                <span class="mx-1 h-4 w-px bg-gray-200" />
                <button type="button" :class="btn(editor.isActive('heading', { level: 2 }))" @click="editor.chain().focus().toggleHeading({ level: 2 }).run()">H2</button>
                <button type="button" :class="btn(editor.isActive('heading', { level: 3 }))" @click="editor.chain().focus().toggleHeading({ level: 3 }).run()">H3</button>
                <button type="button" :class="btn(editor.isActive('bulletList'))" @click="editor.chain().focus().toggleBulletList().run()">• List</button>
                <button type="button" :class="btn(editor.isActive('table'))" @click="editor.chain().focus().insertTable({ rows: 3, cols: 3, withHeaderRow: true }).run()">Table</button>
                <button type="button" :class="btn(false)" @click="imagePickerOpen = true">Image</button>
            </template>
            <template v-if="inTable">
                <span class="mx-1 h-4 w-px bg-gray-200" />
                <button type="button" :class="btn(false)" @click="editor.chain().focus().addColumnAfter().run()">+Col</button>
                <button type="button" :class="btn(false)" @click="editor.chain().focus().deleteColumn().run()">−Col</button>
                <button type="button" :class="btn(false)" @click="editor.chain().focus().addRowAfter().run()">+Row</button>
                <button type="button" :class="btn(false)" @click="editor.chain().focus().deleteRow().run()">−Row</button>
                <button type="button" :class="btn(false)" @click="editor.chain().focus().deleteTable().run()">Del table</button>
            </template>
            <span class="ml-auto text-[11px] text-gray-400">
                {{ busy ? 'Processing…' : (compact ? 'Paste keeps formatting' : 'Type / for blocks · Import .docx/.md') }}
            </span>
        </div>

        <p v-if="!compact" class="border-b border-gray-100 px-3 py-1 text-[11px] text-gray-400">
            Google Docs: File → Download → Microsoft Word (.docx), then Import — or paste directly (Cmd/Ctrl+V).
        </p>

        <p v-if="importError" class="border-b border-rose-100 bg-rose-50 px-3 py-1.5 text-xs text-rose-700">
            {{ importError }}
        </p>

        <EditorContent :editor="editor" class="block-editor" />

        <div
            v-if="slashOpen"
            class="absolute left-3 top-14 z-20 w-56 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg"
        >
            <input
                v-model="slashFilter"
                type="search"
                placeholder="Filter blocks…"
                class="w-full border-b border-gray-100 px-3 py-2 text-sm"
                @keydown.esc="slashOpen = false"
            >
            <button
                v-for="cmd in blockCommands"
                :key="cmd.key"
                type="button"
                class="block w-full px-3 py-2 text-left text-sm text-gray-700 hover:bg-gray-50"
                @click="runCommand(cmd)"
            >
                {{ cmd.label }}
            </button>
            <p v-if="!blockCommands.length" class="px-3 py-2 text-xs text-gray-400">No matches</p>
        </div>

        <Teleport to="body">
            <div
                v-if="imagePickerOpen"
                class="fixed inset-0 z-[210] flex items-center justify-center bg-black/40 p-4"
                @click.self="imagePickerOpen = false"
            >
                <div class="w-full max-w-lg rounded-lg bg-white p-4 shadow-xl">
                    <h3 class="mb-3 text-sm font-semibold text-gray-900">Insert image from library</h3>
                    <AppMediaPicker
                        label="Choose image"
                        :model-value="null"
                        @update:model-value="onImagePicked"
                    />
                    <button type="button" class="mt-3 text-sm text-gray-500 hover:text-gray-800" @click="imagePickerOpen = false">
                        Cancel
                    </button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
:deep(.ProseMirror p.is-editor-empty:first-child::before),
:deep(.ProseMirror .is-empty::before) {
    color: #9ca3af;
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

:deep(.cms-table),
:deep(table) {
    border-collapse: collapse;
    margin: 0.75rem 0;
    table-layout: fixed;
    width: 100%;
}

:deep(.cms-table td),
:deep(.cms-table th),
:deep(table td),
:deep(table th) {
    border: 1px solid #d1d5db;
    min-width: 2.5rem;
    padding: 0.35rem 0.5rem;
    vertical-align: top;
}

:deep(.cms-table th),
:deep(table th) {
    background: #f3f4f6;
    font-weight: 600;
}
</style>
