<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Page-wise FAQs: nullable page_slug ties an FAQ to a data/pages/{slug}.json
 * page. Null = global / unassigned (shown as fallback when a page has none).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->string('page_slug', 191)->nullable()->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('faqs', function (Blueprint $table) {
            $table->dropIndex(['page_slug']);
            $table->dropColumn('page_slug');
        });
    }
};
