<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->string('disk', 20)->default('public');
            $table->string('path')->unique();
            $table->string('kind', 12)->index(); // image | document
            $table->string('original_name');
            $table->string('mime', 80);
            $table->unsignedBigInteger('size');
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->char('sha1', 40)->index();
            $table->json('alt')->nullable();          // {"en": "...", "ar": "..."}
            $table->json('variants')->nullable();     // optimised copies: [{format, width, path}]
            $table->string('status', 12)->default('ready'); // ready | processing | failed
            $table->string('error')->nullable();
            $table->string('source_key')->nullable()->unique(); // set by the reference import, keeps re-runs idempotent
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
