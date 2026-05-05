<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_domain');                   // ziyaret edilen mikro site
            $table->string('locale', 5);                       // form dili
            $table->string('brand', 32)->nullable();
            $table->string('crm_target', 32);                  // 'omnigos' | 'linguland'
            $table->string('form_type', 64)->default('contact'); // contact | brochure | callback ...
            $table->json('payload');                           // form alanları
            $table->string('crm_status', 32)->default('pending'); // pending | sent | failed | skipped
            $table->json('crm_response')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('referrer')->nullable();
            $table->string('user_agent')->nullable();
            $table->ipAddress('ip')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'created_at']);
            $table->index(['crm_target', 'crm_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
