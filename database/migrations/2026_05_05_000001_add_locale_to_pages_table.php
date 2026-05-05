<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('locale', 5)->default('tr')->after('site_id')->index();
            // (site_id, slug) unique idi — locale dahil olunca (site_id, locale, slug) olur
            $table->dropUnique(['site_id', 'slug']);
            $table->unique(['site_id', 'locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['site_id', 'locale', 'slug']);
            $table->unique(['site_id', 'slug']);
            $table->dropColumn('locale');
        });
    }
};
