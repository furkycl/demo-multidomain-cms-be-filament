<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('type');               // header, hero, rich_text, footer, ...
            $table->unsignedInteger('order')->default(0);
            $table->json('content');              // type-specific
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->timestamps();

            $table->index(['page_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
