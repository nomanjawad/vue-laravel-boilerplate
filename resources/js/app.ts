import '../css/app.css'
import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'
import { ZiggyVue, type Config as ZiggyConfig } from 'ziggy-js'
import { Ziggy as ZiggyRaw } from './types/ziggy.js'

// Session-expired (419) recovery. Inertia shows a jarring sandboxed-iframe
// error modal for non-Inertia responses; a 419 CSRF failure is one of them.
// The server-side handler in bootstrap/app.php already redirects 419s back
// with a flash message for the happy path (form submits during expired
// sessions). This client-side handler is defense-in-depth for any 419 that
// slips past — a proxy stripping the redirect, a genuinely broken response
// mid-navigation — where we hard-reload instead of showing Inertia's modal.
router.on('httpException', (event) => {
    if (event.detail.response.status === 419) {
        event.preventDefault()
        window.location.reload()
    }
})

// The generated ziggy.js data types HTTP methods as plain `string[]` while
// ziggy-js's Config expects `("GET"|"HEAD"|...)[]`. The runtime shape is
// correct; only the compile-time widening needs a cast.
const Ziggy = ZiggyRaw as unknown as ZiggyConfig

type PageModule = { default: DefineComponent }

// Core pages under resources/js/Pages and module pages under
// app/Modules/*/Resources/js/Pages are both resolved here. Module pages
// are addressed as `{Module}/{sub-path}`, e.g. Inertia::render(
// 'Testimonials/Admin/Testimonials/Index') resolves to
// app/Modules/Testimonials/Resources/js/Pages/Admin/Testimonials/Index.vue.
const corePages = import.meta.glob<PageModule>('./Pages/**/*.vue', { eager: true })
const modulePages = import.meta.glob<PageModule>('../../app/Modules/*/Resources/js/Pages/**/*.vue', { eager: true })

// Silent brand-name fallbacks silently ship the wrong tab title to production
// when VITE_APP_NAME isn't loaded at build time (e.g. CI that doesn't source
// .env, a `.env` where VITE_APP_NAME="${APP_NAME}" wasn't interpolated).
// Fail the build instead — see feedback.md §5. Projects should set
// VITE_APP_NAME in .env.example alongside APP_NAME.
const APP_NAME = import.meta.env.VITE_APP_NAME
if (!APP_NAME || APP_NAME.trim() === '') {
    throw new Error(
        'VITE_APP_NAME is not set. Add VITE_APP_NAME="${APP_NAME}" to .env before building — ' +
        'shipping a build with an empty brand name would silently show the placeholder in every browser tab.',
    )
}

createInertiaApp({
    title: (title) => title ? `${title} - ${APP_NAME}` : APP_NAME,
    resolve: (name) => {
        const coreKey = `./Pages/${name}.vue`
        const core = corePages[coreKey]
        if (core) return core

        const [moduleName, ...rest] = name.split('/')
        if (!moduleName) {
            throw new Error(`Inertia page name is empty: "${name}"`)
        }
        const moduleKey = `../../app/Modules/${moduleName}/Resources/js/Pages/${rest.join('/')}.vue`
        const mod = modulePages[moduleKey]
        if (!mod) {
            throw new Error(`Inertia page not found: "${name}" (tried ${coreKey} and ${moduleKey})`)
        }
        return mod
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mount(el)
    },
})
