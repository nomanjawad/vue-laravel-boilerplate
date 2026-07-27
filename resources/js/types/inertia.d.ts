/**
 * Shared Inertia props emitted by HandleInertiaRequests.
 * Field shapes are generated from app/Data/* DTOs via typescript:transform.
 *
 * Inertia 3.6+ pattern: augment InertiaConfig['sharedPageProps'] to make
 * usePage() and $page.props return the typed shared props automatically.
 * See node_modules/@inertiajs/core/types/types.d.ts (SharedPageProps).
 */
import '@inertiajs/core'

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
    [key: string]: unknown
}

declare module '@inertiajs/core' {
    interface InertiaConfig {
        sharedPageProps: SharedPageProps
    }
}

declare module 'vue' {
    interface ComponentCustomProperties {
        route: typeof import('ziggy-js').route
    }
}
