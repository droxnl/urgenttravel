<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 10);
            $table->string('path');
            $table->string('title');
            $table->string('hero_eyebrow')->nullable();
            $table->string('hero_title');
            $table->text('hero_subtitle')->nullable();
            $table->text('excerpt')->nullable();
            $table->longText('content_html')->nullable();
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->string('focus_keyword')->nullable();
            $table->string('canonical_url')->nullable();
            $table->string('robots')->default('index, follow');
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->string('og_image')->nullable();
            $table->json('schema_json')->nullable();
            $table->timestamp('modified_at')->nullable();
            $table->timestamps();

            $table->unique(['page_id', 'locale']);
            $table->index(['locale', 'path']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_translations');
    }
};
