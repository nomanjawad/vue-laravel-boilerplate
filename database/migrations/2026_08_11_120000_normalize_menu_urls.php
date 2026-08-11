<?php

use App\Models\Menu;
use Illuminate\Database\Migrations\Migration;

/**
 * Backfill for feedback.md §41: menu URLs saved without a leading slash
 * (e.g. "about" instead of "/about") render as text fine but break Inertia's
 * client-side nav. New saves go through Menu::normalizeUrl(); this fixes
 * rows written before that guard existed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Menu::query()->each(function (Menu $menu) {
            $normalized = Menu::normalizeUrl($menu->url);

            if ($normalized !== $menu->url) {
                $menu->update(['url' => $normalized]);
            }
        });
    }

    public function down(): void
    {
        // Not reversible — the original (broken) URLs aren't recoverable.
    }
};
