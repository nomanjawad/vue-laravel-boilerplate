<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Head, Link, useForm } from '@inertiajs/vue3'

import FormShell from '@/Components/Organisms/FormShell.vue'
import AppFormField from '@/Components/Molecules/AppFormField.vue'
import AppFormSection from '@/Components/Molecules/AppFormSection.vue'
import AppInput from '@/Components/Atoms/AppInput.vue'
import AppTextarea from '@/Components/Atoms/AppTextarea.vue'
import AppSelect from '@/Components/Atoms/AppSelect.vue'
import AppSwitch from '@/Components/Atoms/AppSwitch.vue'

defineOptions({ layout: AdminLayout })

interface CustomCode {
    id: number
    name: string
    placement: string
    code: string
    is_active: boolean
    sort_order: number
}

const props = defineProps<{ code: CustomCode }>()

const PLACEMENT_OPTIONS = [
    { value: 'head', label: 'Head (before </head>)' },
    { value: 'body_start', label: 'Body start (right after <body>)' },
    { value: 'body_end', label: 'Body end (right before </body>)' },
]

const form = useForm({
    name: props.code.name,
    placement: props.code.placement,
    code: props.code.code,
    is_active: props.code.is_active,
    sort_order: props.code.sort_order,
})
</script>

<template>
    <Head title="Edit Custom Code" />
    <div class="mb-6 flex items-center gap-3">
        <Link href="/admin/custom-code" class="text-gray-500 hover:text-gray-700">&larr;</Link>
        <h1 class="text-2xl font-bold text-gray-900">Edit Custom Code</h1>
    </div>

    <FormShell
        :form="form"
        :action="`/admin/custom-code/${code.id}`"
        method="put"
        submit-label="Update Snippet"
        cancel-href="/admin/custom-code"
    >
        <AppFormSection>
            <AppFormField name="name" label="Name" required help="Internal label only — not rendered.">
                <template #default="{ id, invalid }">
                    <AppInput :id="id" v-model="form.name" :invalid="invalid" />
                </template>
            </AppFormField>

            <AppFormField name="placement" label="Placement" required>
                <template #default="{ id, invalid }">
                    <AppSelect :id="id" v-model="form.placement" :options="PLACEMENT_OPTIONS" :invalid="invalid" />
                </template>
            </AppFormField>

            <AppFormField
                name="code"
                label="Code"
                required
                help="Raw HTML/JS/CSS, injected verbatim — no sandboxing. Only add code you trust."
            >
                <template #default="{ id, invalid }">
                    <AppTextarea :id="id" v-model="form.code" :rows="12" :invalid="invalid" />
                </template>
            </AppFormField>

            <AppFormField name="is_active" label="Active">
                <AppSwitch v-model="form.is_active" />
            </AppFormField>
        </AppFormSection>
    </FormShell>
</template>
