<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

abstract class Controller
{
    /**
     * Normalize a stored image path to a browser-usable URL.
     *
     * Content imported from other systems (e.g. WordPress) keeps its files under
     * public/uploads/, while media uploaded through the admin lives on the public
     * disk (served via /storage). Use this everywhere an image path leaves the
     * backend — the JS mirror is resources/js/Composables/useImageUrl.js.
     */
    protected function imageUrl(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://') || str_starts_with($path, '/')) {
            return $path;
        }

        if (str_starts_with($path, 'uploads/')) {
            return '/'.$path;
        }

        return Storage::url($path);
    }
}
