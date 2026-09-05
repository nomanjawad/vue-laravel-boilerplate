/**
 * AUTO-GENERATED from config/widgets.php — do not edit by hand.
 * Regenerate: `php artisan widgets:types` (also runs after typescript:transform).
 */

/** Media-library payload accepted by AppImage (string URL still allowed). */
export type WidgetImage =
    | string
    | {
          url?: string | null
          variants?: Record<string, string | { path?: string; width?: number; height?: number } | null> | null
          width?: number | null
          height?: number | null
          alt_text?: string | null
      }
    | null

/** Collection source block on dynamic widgets. */
export interface WidgetCollection {
    mode?: string
    ids?: Array<number | string>
    limit?: number
    page_slug?: string | null
}

export interface WidgetHeroData {
    title?: string;
    subtitle?: string;
    image?: WidgetImage;
    cta_text?: string;
    cta_url?: string;
    secondary_cta_text?: string;
    secondary_cta_url?: string;
}

export interface WidgetRichTextData {
    title?: string;
    body?: string;
}

export interface WidgetFeatureGridData {
    title?: string;
    items?: Array<{ title?: string; description?: string; icon?: string }>;
}

export interface WidgetStatsData {
    title?: string;
    items?: Array<{ value?: string; label?: string }>;
}

export interface WidgetCtaData {
    title?: string;
    description?: string;
    button_text?: string;
    button_url?: string;
}

export interface WidgetImageData {
    src?: WidgetImage;
    alt?: string;
    caption?: string;
}

export interface WidgetGalleryData {
    title?: string;
    images?: Array<{ src?: WidgetImage; alt?: string }>;
}

export interface WidgetTestimonialsData {
    title?: string;
    collection?: WidgetCollection;
}

export interface WidgetFaqsData {
    title?: string;
    collection?: WidgetCollection;
}

export interface WidgetTeamData {
    title?: string;
    collection?: WidgetCollection;
}

export interface WidgetLatestPostsData {
    title?: string;
    collection?: WidgetCollection;
}

export interface WidgetContactFormData {
    heading?: string;
    info_heading?: string;
    office_hours?: string;
    map_embed?: string;
    show_contact_info?: boolean;
}

export interface WidgetCustomHtmlData {
    html?: string;
}

export type WidgetDataByType = {
    'hero': WidgetHeroData
    'rich_text': WidgetRichTextData
    'feature_grid': WidgetFeatureGridData
    'stats': WidgetStatsData
    'cta': WidgetCtaData
    'image': WidgetImageData
    'gallery': WidgetGalleryData
    'testimonials': WidgetTestimonialsData
    'faqs': WidgetFaqsData
    'team': WidgetTeamData
    'latest_posts': WidgetLatestPostsData
    'contact_form': WidgetContactFormData
    'custom_html': WidgetCustomHtmlData
}

export type PageWidget =
    | { id: string; type: 'hero'; visible?: boolean; data?: WidgetHeroData }
    | { id: string; type: 'rich_text'; visible?: boolean; data?: WidgetRichTextData }
    | { id: string; type: 'feature_grid'; visible?: boolean; data?: WidgetFeatureGridData }
    | { id: string; type: 'stats'; visible?: boolean; data?: WidgetStatsData }
    | { id: string; type: 'cta'; visible?: boolean; data?: WidgetCtaData }
    | { id: string; type: 'image'; visible?: boolean; data?: WidgetImageData }
    | { id: string; type: 'gallery'; visible?: boolean; data?: WidgetGalleryData }
    | { id: string; type: 'testimonials'; visible?: boolean; data?: WidgetTestimonialsData }
    | { id: string; type: 'faqs'; visible?: boolean; data?: WidgetFaqsData }
    | { id: string; type: 'team'; visible?: boolean; data?: WidgetTeamData }
    | { id: string; type: 'latest_posts'; visible?: boolean; data?: WidgetLatestPostsData }
    | { id: string; type: 'contact_form'; visible?: boolean; data?: WidgetContactFormData }
    | { id: string; type: 'custom_html'; visible?: boolean; data?: WidgetCustomHtmlData }

export type WidgetType = keyof WidgetDataByType
