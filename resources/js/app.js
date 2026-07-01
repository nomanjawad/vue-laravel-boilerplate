import '../css/app.css'
import { createApp, h } from 'vue'
import { createInertiaApp } from '@inertiajs/vue3'
import { ZiggyVue } from 'ziggy-js'
import { Ziggy } from './types/ziggy'

createInertiaApp({
    title: (title) => title ? `${title} - ${import.meta.env.VITE_APP_NAME || 'WebTemplate'}` : (import.meta.env.VITE_APP_NAME || 'WebTemplate'),
    resolve: (name) => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true })
        return pages[`./Pages/${name}.vue`]
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue, Ziggy)
            .mount(el)
    },
})
