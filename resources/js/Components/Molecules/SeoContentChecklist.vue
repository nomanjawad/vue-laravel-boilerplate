<script setup lang="ts">
/**
 * Lightweight RankMath-style content checklist (client-side only).
 * Checks focus keyword placement, lengths, word count, image alts, H2, internal link.
 */
import { computed } from 'vue'

interface Props {
    focusKeyword: string
    title: string
    slug: string
    metaTitle: string
    metaDescription: string
    bodyHtml: string
}

const props = defineProps<Props>()

interface Check {
    id: string
    label: string
    pass: boolean
    skip?: boolean
}

function stripTags(html: string): string {
    return html.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim()
}

function firstParagraph(html: string): string {
    const match = html.match(/<p\b[^>]*>([\s\S]*?)<\/p>/i)
    return match ? stripTags(match[1] ?? '') : stripTags(html).slice(0, 280)
}

const checks = computed<Check[]>(() => {
    const kw = props.focusKeyword.trim().toLowerCase()
    const hasKw = kw.length > 0
    const title = (props.metaTitle || props.title).toLowerCase()
    const slug = props.slug.toLowerCase()
    const desc = props.metaDescription.toLowerCase()
    const body = props.bodyHtml || ''
    const plain = stripTags(body)
    const words = plain ? plain.split(/\s+/).filter(Boolean).length : 0
    const first = firstParagraph(body).toLowerCase()

    const imgs = [...body.matchAll(/<img\b[^>]*>/gi)].map((m) => m[0] ?? '')
    const allImgsHaveAlt = imgs.length === 0 || imgs.every((tag) => {
        const alt = tag.match(/\balt\s*=\s*(["'])(.*?)\1/i)
        return Boolean(alt && (alt[2] ?? '').trim() !== '')
    })

    const hasH2 = /<h2\b/i.test(body)
    const hasInternalLink = /<a\b[^>]*\bhref\s*=\s*(["'])(\/[^"']*|https?:\/\/[^"']+)\1/i.test(body)
        && /<a\b[^>]*\bhref\s*=\s*(["'])(\/[^"']*)\1/i.test(body)

    const titleLen = (props.metaTitle || props.title).trim().length
    const descLen = props.metaDescription.trim().length

    return [
        {
            id: 'kw-title',
            label: 'Focus keyword in SEO / post title',
            pass: hasKw && title.includes(kw),
            skip: !hasKw,
        },
        {
            id: 'kw-slug',
            label: 'Focus keyword in slug',
            pass: hasKw && slug.includes(kw.replace(/\s+/g, '-')),
            skip: !hasKw,
        },
        {
            id: 'kw-desc',
            label: 'Focus keyword in meta description',
            pass: hasKw && desc.includes(kw),
            skip: !hasKw,
        },
        {
            id: 'kw-first',
            label: 'Focus keyword in first paragraph',
            pass: hasKw && first.includes(kw),
            skip: !hasKw,
        },
        {
            id: 'title-len',
            label: 'Title length ≤ 60 characters',
            pass: titleLen > 0 && titleLen <= 60,
        },
        {
            id: 'desc-len',
            label: 'Meta description 50–160 characters',
            pass: descLen >= 50 && descLen <= 160,
        },
        {
            id: 'words',
            label: 'Body has at least 300 words',
            pass: words >= 300,
        },
        {
            id: 'img-alt',
            label: 'Every image has alt text',
            pass: allImgsHaveAlt,
        },
        {
            id: 'h2',
            label: 'Has at least one H2 heading',
            pass: hasH2,
        },
        {
            id: 'internal',
            label: 'Has at least one internal link',
            pass: hasInternalLink,
        },
    ]
})

const visible = computed(() => checks.value.filter((c) => !c.skip))
const passed = computed(() => visible.value.filter((c) => c.pass).length)
</script>

<template>
    <div class="rounded-lg border border-gray-200 bg-white p-4">
        <div class="mb-3 flex items-baseline justify-between gap-2">
            <h4 class="text-sm font-semibold text-gray-900">Content checklist</h4>
            <span class="text-xs text-gray-500">{{ passed }}/{{ visible.length }}</span>
        </div>
        <p v-if="!focusKeyword.trim()" class="mb-3 text-xs text-amber-700">
            Add a focus keyword below to enable keyword checks.
        </p>
        <ul class="space-y-1.5">
            <li
                v-for="check in visible"
                :key="check.id"
                class="flex items-start gap-2 text-sm"
            >
                <span
                    class="mt-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-full text-[10px] font-bold text-white"
                    :class="check.pass ? 'bg-emerald-500' : 'bg-gray-300'"
                    aria-hidden="true"
                >
                    {{ check.pass ? '✓' : '·' }}
                </span>
                <span :class="check.pass ? 'text-gray-700' : 'text-gray-500'">{{ check.label }}</span>
            </li>
        </ul>
    </div>
</template>
