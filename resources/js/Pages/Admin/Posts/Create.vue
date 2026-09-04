<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'
import AppBlockEditor from '@/Components/Organisms/AppBlockEditor.vue'
import AppFloatingSave from '@/Components/Molecules/AppFloatingSave.vue'
import PostCategoriesField from '@/Components/Molecules/PostCategoriesField.vue'
import SeoSerpPreview from '@/Components/Molecules/SeoSerpPreview.vue'
import SeoContentChecklist from '@/Components/Molecules/SeoContentChecklist.vue'

defineOptions({ layout: AdminLayout })

interface Props {
    categories: App.Data.CategorySummaryData[]
    tags: App.Data.TagSummaryData[]
}

defineProps<Props>()

interface PostForm {
    title: string
    slug: string
    excerpt: string
    body: string
    categories: number[]
    status: string
    featured_image: string
    meta_title: string
    meta_description: string
    og_image: string
    canonical_url: string
    og_title: string
    og_description: string
    focus_keyword: string
    noindex: boolean
    tags: number[]
}

const form = useForm<PostForm>({
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    categories: [],
    status: 'draft',
    featured_image: '',
    meta_title: '',
    meta_description: '',
    og_image: '',
    canonical_url: '',
    og_title: '',
    og_description: '',
    focus_keyword: '',
    noindex: false,
    tags: [],
})

type PickerMedia = { id: number | string; url?: string | null; variants?: Record<string, import('@/Composables/useImageUrl').VariantEntry> | null }

function urlAsMedia(url: string): PickerMedia | null {
    return url ? { id: 0, url } : null
}

function setUrlFromMedia(field: 'featured_image' | 'og_image', media: PickerMedia | null) {
    form[field] = media?.url ?? ''
}

const submit = () => {
    form.post('/admin/posts')
}
</script>

<template>
    <Head title="Create Post" />
    <div class="flex items-center mb-6">
        <Link href="/admin/posts" class="text-gray-500 hover:text-gray-700 mr-2">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Create Post</h1>
    </div>

    <form @submit.prevent="submit" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Title</label>
                    <input v-model="form.title" type="text" required class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                    <p v-if="form.errors.title" class="mt-1 text-sm text-red-600">{{ form.errors.title }}</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Slug <span class="text-gray-400">(auto-generated if empty)</span></label>
                    <input v-model="form.slug" type="text" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Excerpt</label>
                    <textarea v-model="form.excerpt" rows="2" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Body</label>
                    <AppBlockEditor v-model="form.body" placeholder="Write your post… Type / for blocks" />
                    <p v-if="form.errors.body" class="mt-1 text-sm text-red-600">{{ form.errors.body }}</p>
                </div>
            </div>

            <!-- SEO -->
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <h3 class="text-lg font-semibold text-gray-900">SEO</h3>
                <SeoSerpPreview
                    :title="form.meta_title || form.title"
                    :description="form.meta_description"
                    :url="`https://example.com/blog/${form.slug || 'post-slug'}`"
                />
                <div>
                    <label class="block text-sm font-medium text-gray-700">Focus keyword</label>
                    <input v-model="form.focus_keyword" type="text" placeholder="e.g. dental implants" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <SeoContentChecklist
                    :focus-keyword="form.focus_keyword"
                    :title="form.title"
                    :slug="form.slug"
                    :meta-title="form.meta_title"
                    :meta-description="form.meta_description"
                    :body-html="form.body"
                />
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meta Title</label>
                    <input v-model="form.meta_title" type="text" placeholder="Falls back to title template" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Meta Description</label>
                    <textarea v-model="form.meta_description" rows="2" placeholder="Aim for 50–160 characters" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Canonical URL</label>
                    <input v-model="form.canonical_url" type="text" placeholder="https://example.com/blog/… (optional)" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">OG Title</label>
                    <input v-model="form.og_title" type="text" placeholder="Optional social title" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">OG Description</label>
                    <textarea v-model="form.og_description" rows="2" placeholder="Optional social description" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500" />
                </div>
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Social share image</label>
                    <AppMediaPicker
                        label="OG image"
                        :model-value="urlAsMedia(form.og_image)"
                        @update:model-value="(m) => setUrlFromMedia('og_image', m)"
                    />
                </div>
                <label class="flex items-center gap-2">
                    <input v-model="form.noindex" type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500" />
                    <span class="text-sm font-medium text-gray-700">No-index (hide from search engines)</span>
                </label>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <div class="bg-white rounded-lg shadow p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select v-model="form.status" class="mt-1 block w-full rounded-md border border-gray-300 px-3 py-2 shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500">
                        <option value="draft">Draft</option>
                        <option value="published">Published</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <PostCategoriesField v-model="form.categories" :categories="categories" />
                <div>
                    <label class="mb-1 block text-sm font-medium text-gray-700">Featured image</label>
                    <AppMediaPicker
                        label="Featured image"
                        :model-value="urlAsMedia(form.featured_image)"
                        @update:model-value="(m) => setUrlFromMedia('featured_image', m)"
                    />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tags</label>
                    <div class="space-y-1 max-h-40 overflow-y-auto">
                        <label v-for="tag in tags" :key="tag.id" class="flex items-center">
                            <input v-model="form.tags" :value="tag.id" type="checkbox" class="rounded border-gray-300 text-gray-900 focus:ring-gray-500" />
                            <span class="ml-2 text-sm text-gray-700">{{ tag.name }}</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <AppFloatingSave
        :dirty="form.isDirty"
        :processing="form.processing"
        dirty-label="Create post"
        label="Create post"
        @save="submit"
    />
</template>
