<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import FormShell from '@/Components/Organisms/FormShell.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppCheckbox from '@/Components/Atoms/AppCheckbox.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'
import AppMediaPicker from '@/Components/Organisms/AppMediaPicker.vue'
import AppBlockEditor from '@/Components/Organisms/AppBlockEditor.vue'
import AppFloatingSave from '@/Components/Molecules/AppFloatingSave.vue'
import PostCategoriesField from '@/Components/Molecules/PostCategoriesField.vue'
import SeoSerpPreview from '@/Components/Molecules/SeoSerpPreview.vue'
import SeoContentChecklist from '@/Components/Molecules/SeoContentChecklist.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps<{
    categories: App.Data.CategorySummaryData[]
    tags: App.Data.TagSummaryData[]
}>()

const form = useForm({
    title: '',
    slug: '',
    excerpt: '',
    body: '',
    categories: [] as number[],
    status: 'draft',
    featured_image: '',
    meta_title: '',
    meta_description: '',
    focus_keyword: '',
    noindex: false,
    tags: [] as number[],
})

type PickerMedia = { id: number | string; url?: string | null; variants?: Record<string, import('@/Composables/useImageUrl').VariantEntry> | null }

function urlAsMedia(url: string): PickerMedia | null {
    return url ? { id: 0, url } : null
}

function setUrlFromMedia(field: 'featured_image', media: PickerMedia | null) {
    form[field] = media?.url ?? ''
}

function toggleTag(id: number) {
    form.tags = form.tags.includes(id) ? form.tags.filter((t) => t !== id) : [...form.tags, id]
}

function submit() {
    form.post('/admin/posts')
}
</script>

<template>
    <Head title="Create Post" />
    <div class="mb-6 flex items-center">
        <Link href="/admin/posts" class="mr-2 text-gray-500 hover:text-gray-700">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Create Post</h1>
    </div>

    <FormShell
        :form="form"
        action="/admin/posts"
        method="post"
        submit-label="Create Post"
        cancel-href="/admin/posts"
        hide-actions
    >
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <AppFormSection title="Content">
                    <AppFormField name="title" label="Title" required>
                        <template #default="{ id, invalid }">
                            <AppInput :id="id" v-model="form.title" :invalid="invalid" />
                        </template>
                    </AppFormField>
                    <AppFormField name="slug" label="Slug" help="Auto-generated from the title if left empty.">
                        <template #default="{ id, invalid }">
                            <AppInput :id="id" v-model="form.slug" :invalid="invalid" />
                        </template>
                    </AppFormField>
                    <AppFormField name="excerpt" label="Excerpt">
                        <template #default="{ id, invalid }">
                            <AppTextarea :id="id" v-model="form.excerpt" :rows="2" :invalid="invalid" />
                        </template>
                    </AppFormField>
                    <AppFormField name="body" label="Body" required>
                        <AppBlockEditor v-model="form.body" placeholder="Write your post… Type / for blocks" />
                    </AppFormField>
                </AppFormSection>
            </div>

            <div class="space-y-6">
                <AppFormSection title="Publishing">
                    <AppFormField name="status" label="Status">
                        <template #default="{ id, invalid }">
                            <AppSelect
                                :id="id"
                                v-model="form.status"
                                :invalid="invalid"
                                :options="[
                                    { value: 'draft', label: 'Draft' },
                                    { value: 'published', label: 'Published' },
                                    { value: 'archived', label: 'Archived' },
                                ]"
                            />
                        </template>
                    </AppFormField>
                    <PostCategoriesField v-model="form.categories" :categories="props.categories" />
                    <AppFormField name="featured_image" label="Featured image">
                        <AppMediaPicker
                            label="Featured image"
                            :model-value="urlAsMedia(form.featured_image)"
                            @update:model-value="(m) => setUrlFromMedia('featured_image', m)"
                        />
                    </AppFormField>
                </AppFormSection>

                <AppFormSection v-if="props.tags.length" title="Tags">
                    <div class="max-h-40 space-y-1 overflow-y-auto">
                        <label v-for="tag in props.tags" :key="tag.id" class="flex items-center gap-2">
                            <AppCheckbox
                                :model-value="form.tags.includes(tag.id)"
                                @update:model-value="toggleTag(tag.id)"
                            />
                            <span class="text-sm text-gray-700">{{ tag.name }}</span>
                        </label>
                    </div>
                </AppFormSection>

                <AppFormSection title="SEO" description="Canonical and Open Graph tags are derived automatically from meta + featured image.">
                    <SeoSerpPreview
                        :title="form.meta_title || form.title"
                        :description="form.meta_description"
                        :url="`https://example.com/blog/${form.slug || 'post-slug'}`"
                    />
                    <AppFormField name="focus_keyword" label="Focus keyword" help="Optional. Powers the content checklist below.">
                        <template #default="{ id, invalid }">
                            <AppInput :id="id" v-model="form.focus_keyword" placeholder="e.g. dental implants" :invalid="invalid" />
                        </template>
                    </AppFormField>
                    <SeoContentChecklist
                        :focus-keyword="form.focus_keyword"
                        :title="form.title"
                        :slug="form.slug"
                        :meta-title="form.meta_title"
                        :meta-description="form.meta_description"
                        :body-html="form.body"
                    />
                    <AppFormField name="meta_title" label="Meta Title" help="Leave blank to use the site title template.">
                        <template #default="{ id, invalid }">
                            <AppInput :id="id" v-model="form.meta_title" placeholder="Falls back to title template" :invalid="invalid" />
                        </template>
                    </AppFormField>
                    <AppFormField name="meta_description" label="Meta Description">
                        <template #default="{ id, invalid }">
                            <AppTextarea :id="id" v-model="form.meta_description" :rows="2" placeholder="Aim for 50–160 characters" :invalid="invalid" />
                        </template>
                    </AppFormField>
                    <AppFormField name="noindex" label="No-index" help="Hide this post from search engines.">
                        <AppSwitch v-model="form.noindex" />
                    </AppFormField>
                </AppFormSection>
            </div>
        </div>
    </FormShell>

    <AppFloatingSave
        :dirty="form.isDirty"
        :processing="form.processing"
        dirty-label="Create post"
        label="Create post"
        @save="submit"
    />
</template>
