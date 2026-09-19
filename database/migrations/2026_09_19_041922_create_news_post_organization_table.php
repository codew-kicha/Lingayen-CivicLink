<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('news_post_organization', function (Blueprint $table) {
            $table->id();
            $table->foreignId('news_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['news_post_id', 'organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('news_post_organization');
    }
};
