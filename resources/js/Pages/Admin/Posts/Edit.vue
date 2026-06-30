<script setup>
// Reference page: how a v3 admin form is composed from
// Atoms (Input/Textarea/Select/Checkbox) → Molecules (FormField/FormSection) →
// Organism (FormShell). Errors auto-wire through the injected useForm().
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, useForm, Link } from '@inertiajs/vue3'
import { computed } from 'vue'

import FormShell from '@/Components/Organisms/FormShell.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppCheckbox from '@/Components/Atoms/AppCheckbox.vue'

defineOptions({ layout: AdminLayout })

const props = defineProps({
    post: { type: Object, required: true },
    categories: { type: Array, default: () => [] },
    tags: { type: Array, default: () => [] },
    previewUrl: { type: String, default: null },
})

const form = useForm({
    title: props.post.title,
    slug: props.post.slug,
    excerpt: props.post.excerpt || '',
    body: props.post.body,
    category_id: props.post.category_id || '',
    status: props.post.status,
    featured_image: props.post.featured_image || '',
    meta_title: props.post.meta_title || '',
    meta_description: props.post.meta_description || '',
    tags: props.post.tags?.map((t) => t.id) || [],
})

const categoryOptions = computed(() => [
    { value: '', label: 'None' },
    ...props.categories.map((c) => ({ value: c.id, label: c.name })),
])

function toggleTag(id) {
    form.tags = form.tags.includes(id) ? form.tags.filter((t) => t !== id) : [...form.tags, id]
}
</script>

<template>
    <Head title="Edit Post" />

    <div class="mb-6 flex items-center gap-3">
        <Link href="/admin/posts" class="text-gray-500 hover:text-gray-700">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Edit Post</h1>
        <a
            v-if="previewUrl"
            :href="previewUrl"
            target="_blank"
            class="ml-auto text-sm text-indigo-600 hover:text-indigo-800"
        >
            Preview {{ post.status !== 'published' ? 'draft' : '' }} ↗
        </a>
    </div>

    <FormShell
        :form="form"
        :action="`/admin/posts/${post.id}`"
        method="put"
        submit-label="Update Post"
        cancel-href="/admin/posts"
    >
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <AppFormSection title="Content">
                    <AppFormField name="title" label="Title" required>
                        <template #default="{ id, invalid }">
                            <AppInput :id="id" v-model="form.title" :invalid="invalid" />
                        </template>
                    </AppFormField>

                    <AppFormField name="slug" label="Slug" help="Used in the URL. Changing this auto-creates a 301.">
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
                        <template #default="{ id, invalid }">
                            <AppTextarea :id="id" v-model="form.body" :rows="15" :invalid="invalid" />
                        </template>
                    </AppFormField>
                </AppFormSection>

                <AppFormSection title="SEO" description="Per-page overrides. Falls back to global defaults.">
                    <AppFormField name="meta_title" label="Meta Title">
                        <template #default="{ id, invalid }">
                            <AppInput :id="id" v-model="form.meta_title" :invalid="invalid" />
                        </template>
                    </AppFormField>
                    <AppFormField name="meta_description" label="Meta Description">
                        <template #default="{ id, invalid }">
                            <AppTextarea :id="id" v-model="form.meta_description" :rows="2" :invalid="invalid" />
                        </template>
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

                    <AppFormField name="category_id" label="Category">
                        <template #default="{ id, invalid }">
                            <AppSelect :id="id" v-model="form.category_id" :options="categoryOptions" :invalid="invalid" />
                        </template>
                    </AppFormField>

                    <AppFormField name="featured_image" label="Featured Image URL">
                        <template #default="{ id, invalid }">
                            <AppInput :id="id" v-model="form.featured_image" :invalid="invalid" />
                        </template>
                    </AppFormField>
                </AppFormSection>

                <AppFormSection v-if="tags.length" title="Tags">
                    <div class="max-h-40 space-y-1 overflow-y-auto">
                        <label v-for="tag in tags" :key="tag.id" class="flex items-center gap-2">
                            <AppCheckbox
                                :model-value="form.tags.includes(tag.id)"
                                @update:model-value="toggleTag(tag.id)"
                            />
                            <span class="text-sm text-gray-700">{{ tag.name }}</span>
                        </label>
                    </div>
                </AppFormSection>
            </div>
        </div>
    </FormShell>
</template>
