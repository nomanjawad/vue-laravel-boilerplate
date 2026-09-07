import '../css/app.css'
import { createApp, h, type DefineComponent } from 'vue'
import { createInertiaApp, router } from '@inertiajs/vue3'

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

type PageModule = { default: DefineComponent }
type PageLoader = () => Promise<PageModule>

// Lazy page graph — Vite emits per-page chunks so anonymous visitors do not
// download TipTap / admin screens (F12 #1). Module pages are addressed as
// `{Module}/{sub-path}`, e.g. Inertia::render('Testimonials/Admin/…').
const corePages = import.meta.glob<PageModule>('./Pages/**/*.vue') as Record<string, PageLoader>
const modulePages = import.meta.glob<PageModule>(
    '../../app/Modules/*/Resources/js/Pages/**/*.vue',
) as Record<string, PageLoader>

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
    // PublicLayout passes a fully-resolved seo.title (template already applied).
    // Admin pages pass short labels — append the site name unless already present.
    title: (title) => {
        if (!title) return APP_NAME
        if (title === APP_NAME || title.endsWith(` — ${APP_NAME}`) || title.endsWith(` - ${APP_NAME}`)) {
            return title
        }
        return `${title} — ${APP_NAME}`
    },
    resolve: (name) => {
        const coreKey = `./Pages/${name}.vue`
        const core = corePages[coreKey]
        if (core) {
            return core().then((m) => m.default)
        }

        const [moduleName, ...rest] = name.split('/')
        if (!moduleName) {
            throw new Error(`Inertia page name is empty: "${name}"`)
        }
        const moduleKey = `../../app/Modules/${moduleName}/Resources/js/Pages/${rest.join('/')}.vue`
        const mod = modulePages[moduleKey]
        if (!mod) {
            throw new Error(`Inertia page not found: "${name}" (tried ${coreKey} and ${moduleKey})`)
        }
        return mod().then((m) => m.default)
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) })

        // Last-resort logging for render/lifecycle errors nothing else caught
        // (widget errors are contained by WidgetBoundary and never reach here).
        // Without a handler Vue only warns in dev; in production a silent
        // throw can blank the page with no trace. Sentry's Vue integration
        // wraps this handler when installed, so reporting stays wired too.
        app.config.errorHandler = (err, _instance, info) => {
            console.error(`[app] unhandled error (${info})`, err)
        }

        app.use(plugin).mount(el)
    },
})
