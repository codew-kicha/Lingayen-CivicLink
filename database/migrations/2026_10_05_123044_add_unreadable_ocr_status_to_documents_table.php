<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // "unreadable" separates a blurry or handwritten scan from a wrong document; ocr_details keeps
    // which expected words were found (never the extracted text, which can hold personal data).
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->enum('ocr_status', ['not_checked', 'pending', 'matched', 'mismatch', 'unreadable'])
                ->default('not_checked')->change();
            $table->json('ocr_details')->nullable()->after('ocr_status');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn('ocr_details');
            $table->enum('ocr_status', ['not_checked', 'pending', 'matched', 'mismatch'])
                ->default('not_checked')->change();
        });
    }
};
