declare namespace App {
namespace Data {
export type AuthData = {
user: App.Data.UserData | null,
};
export type CategorySummaryData = {
id: number,
name: string,
};
export type FlashData = {
success?: string | null,
error?: string | null,
info?: string | null,
warning?: string | null,
media?: App.Data.MediaData | null,
};
export type MediaData = {
id: number,
url: string,
variants: Record<string, string> | null,
alt_text: string | null,
filename: string,
mime_type: string,
size: number,
};
export type MenuItemData = {
id: number,
title: string,
url: string,
sort_order: number,
};
export type MenusData = {
header: App.Data.MenuItemData[],
footer: App.Data.MenuItemData[],
};
export type ModuleNavEntry = {
module: string,
label: string,
route: string | null,
href: string | null,
icon: string,
permission: string | null,
};
export type ModulesSharedData = {
nav: App.Data.ModuleNavEntry[],
enabled: string[],
};
export type PostData = {
id: number,
title: string,
slug: string,
excerpt: string | null,
body: string,
category_id: number | null,
status: string,
featured_image: string | null,
meta_title: string | null,
meta_description: string | null,
category: App.Data.CategorySummaryData | null,
user: App.Data.UserSummaryData | null,
tags: App.Data.TagSummaryData[],
};
export type SearchGroupData = {
module: string,
label: string,
results: App.Data.SearchResultData[],
};
export type SearchResultData = {
id: string | number,
title: string,
subtitle: string | null,
href: string,
};
export type SeoData = {
site_name: string,
title: string | null,
description: string,
og_image: string | null,
canonical: string,
};
export type SettingsData = {
site_name?: string | null,
site_description?: string | null,
site_logo?: string | null,
site_favicon?: string | null,
og_image?: string | null,
contact_email?: string | null,
contact_phone?: string | null,
address?: string | null,
whatsapp?: string | null,
facebook?: string | null,
twitter?: string | null,
instagram?: string | null,
linkedin?: string | null,
youtube?: string | null,
shop_location?: string | null,
shop_currency?: string | null,
shop_currency_symbol?: string | null,
ga_measurement_id?: string | null,
gtm_container_id?: string | null,
cookie_consent_text?: string | null,
};
export type TagSummaryData = {
id: number,
name: string,
slug: string,
};
export type UserData = {
id: number,
name: string,
email: string,
roles: string[],
permissions: string[],
is_super_admin: boolean,
};
export type UserSummaryData = {
id: number,
name: string,
};
}
}
declare namespace Illuminate {
export type CursorPaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
path: string,
per_page: number,
next_cursor: string | null,
next_page_url: string | null,
prev_cursor: string | null,
prev_page_url: string | null,
},
};
export type CursorPaginatorInterface<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type LengthAwarePaginator<TKey, TValue> = {
data: TKey extends string ? Record<TKey, TValue> : TValue[],
links: {
url: string | null,
label: string,
active: boolean,
}[],
meta: {
total: number,
current_page: number,
first_page_url: string,
from: number | null,
last_page: number,
last_page_url: string,
next_page_url: string | null,
path: string,
per_page: number,
prev_page_url: string | null,
to: number | null,
},
};
export type LengthAwarePaginatorInterface<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
declare namespace Spatie {
namespace LaravelData {
export type CursorPaginatedDataCollection<TKey, TValue> = Illuminate.CursorPaginator<TKey, TValue>;
export type PaginatedDataCollection<TKey, TValue> = Illuminate.LengthAwarePaginator<TKey, TValue>;
}
}
