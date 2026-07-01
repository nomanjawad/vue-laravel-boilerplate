import type { PageProps as InertiaPageProps } from '@inertiajs/core'

/**
 * Shared Inertia props emitted by HandleInertiaRequests.
 * Field shapes are generated from app/Data/* DTOs via typescript:transform.
 */
export interface SharedPageProps {
    auth: App.Data.AuthData
    modules: App.Data.ModulesSharedData
    flash: App.Data.FlashData
    menus: App.Data.MenusData
    settings: App.Data.SettingsData
    enabledFeatures: Record<string, boolean>
    cartCount: number
    seo: App.Data.SeoData
    organizationJsonLd: Record<string, unknown> | null
}

declare module '@inertiajs/core' {
    interface PageProps extends SharedPageProps, InertiaPageProps {}
}

declare module '@inertiajs/vue3' {
    interface PageProps extends SharedPageProps, InertiaPageProps {}
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof import('ziggy-js').route
    }
}
