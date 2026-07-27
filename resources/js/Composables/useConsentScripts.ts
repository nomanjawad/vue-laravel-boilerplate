import { ref, type Ref } from 'vue'

const CONSENT_KEY = 'cookie-consent'

/**
 * Cookie-consent gate for analytics scripts (GDPR).
 *
 * GA/GTM ids come from whitelisted site settings and are injected ONLY after
 * the visitor accepts the cookie banner. Never <script src> analytics directly
 * in a layout — it would bypass consent.
 */
export type ConsentValue = 'accepted' | 'declined' | null

export interface ConsentSettings {
    ga_measurement_id?: string | null
    gtm_container_id?: string | null
}

export function useConsentScripts(): {
    consent: Ref<ConsentValue>
    accept: (settings?: ConsentSettings | null) => void
    decline: () => void
    initialize: (settings?: ConsentSettings | null) => void
} {
    const consent: Ref<ConsentValue> = ref(
        (localStorage.getItem(CONSENT_KEY) as ConsentValue) ?? null,
    )

    const injectScripts = (settings?: ConsentSettings | null) => {
        if (document.getElementById('consent-scripts')) return

        const marker = document.createElement('meta')
        marker.id = 'consent-scripts'
        document.head.appendChild(marker)

        const ga = settings?.ga_measurement_id
        if (ga) {
            const tag = document.createElement('script')
            tag.async = true
            tag.src = `https://www.googletagmanager.com/gtag/js?id=${encodeURIComponent(ga)}`
            document.head.appendChild(tag)

            const inline = document.createElement('script')
            inline.textContent = `window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '${ga}');`
            document.head.appendChild(inline)
        }

        const gtm = settings?.gtm_container_id
        if (gtm) {
            const inline = document.createElement('script')
            inline.textContent = `(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src='https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);})(window,document,'script','dataLayer','${gtm}');`
            document.head.appendChild(inline)
        }
    }

    const accept = (settings?: ConsentSettings | null) => {
        localStorage.setItem(CONSENT_KEY, 'accepted')
        consent.value = 'accepted'
        injectScripts(settings)
    }

    const decline = () => {
        localStorage.setItem(CONSENT_KEY, 'declined')
        consent.value = 'declined'
    }

    // Call on mount: re-inject for returning visitors who already accepted.
    const initialize = (settings?: ConsentSettings | null) => {
        if (consent.value === 'accepted') injectScripts(settings)
    }

    return { consent, accept, decline, initialize }
}
