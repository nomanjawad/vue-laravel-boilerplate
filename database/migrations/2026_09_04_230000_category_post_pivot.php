<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * WordPress-style many-to-many categories: pivot replaces posts.category_id,
 * and every install gets an undeletable "Uncategorized" fallback.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_post', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('post_id')->constrained()->cascadeOnDelete();
            $table->unique(['category_id', 'post_id']);
        });

        if (Schema::hasColumn('posts', 'category_id')) {
            $rows = DB::table('posts')
                ->whereNotNull('category_id')
                ->get(['id', 'category_id']);

            foreach ($rows as $row) {
                DB::table('category_post')->insertOrIgnore([
                    'category_id' => $row->category_id,
                    'post_id' => $row->id,
                ]);
            }

            Schema::table('posts', function (Blueprint $table) {
                $table->dropConstrainedForeignId('category_id');
            });
        }

        $now = now();
        if (! DB::table('categories')->where('slug', 'uncategorized')->exists()) {
            DB::table('categories')->insert([
                'name' => 'Uncategorized',
                'slug' => 'uncategorized',
                'description' => null,
                'parent_id' => null,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $uncategorizedId = DB::table('categories')->where('slug', 'uncategorized')->value('id');
        if ($uncategorizedId) {
            $assigned = DB::table('category_post')->distinct()->pluck('post_id');
            $orphans = DB::table('posts')->whereNotIn('id', $assigned)->pluck('id');
            foreach ($orphans as $postId) {
                DB::table('category_post')->insertOrIgnore([
                    'category_id' => $uncategorizedId,
                    'post_id' => $postId,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
        });

        $firsts = DB::table('category_post')
            ->select('post_id', DB::raw('MIN(category_id) as category_id'))
            ->groupBy('post_id')
            ->get();

        foreach ($firsts as $row) {
            DB::table('posts')->where('id', $row->post_id)->update(['category_id' => $row->category_id]);
        }

        Schema::dropIfExists('category_post');
    }
};
