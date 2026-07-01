import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import { Ziggy } from './types/ziggy'

// Core pages under resources/js/Pages and module pages under
// app/Modules/*/Resources/js/Pages are both resolved here. Module pages
// are addressed as `{Module}/{sub-path}`, e.g. Inertia::render(
// 'Testimonials/Admin/Testimonials/Index') resolves to
// app/Modules/Testimonials/Resources/js/Pages/Admin/Testimonials/Index.vue.
const corePages = import.meta.glob('./Pages/**/*.vue', { eager: true })
const modulePages = import.meta.glob('../../app/Modules/*/Resources/js/Pages/**/*.vue', { eager: true })

createInertiaApp({
    title: (title) => title ? `${title} - ${import.meta.env.VITE_APP_NAME || 'WebTemplate'}` : (import.meta.env.VITE_APP_NAME || 'WebTemplate'),
    resolve: (name) => {
        const coreKey = `./Pages/${name}.vue`
        if (corePages[coreKey]) return corePages[coreKey]

        const [moduleName, ...rest] = name.split('/')
        const moduleKey = `../../app/Modules/${moduleName}/Resources/js/Pages/${rest.join('/')}.vue`
        return modulePages[moduleKey]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mount(el)
    },
})
