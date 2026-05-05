<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->string('brand', 32)->nullable()->after('name')->index();
            $table->string('city', 80)->nullable()->after('brand');
            $table->string('country', 2)->nullable()->after('city')->comment('ISO 3166-1 alpha-2');
            $table->json('default_locales')->nullable()->after('country')
                ->comment('Bu sitenin yayında olduğu locale listesi; null ise config supported tüm liste');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn(['brand', 'city', 'country', 'default_locales']);
        });
    }
};
