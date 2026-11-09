<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Append-only publishing history. Each row is a complete, immutable snapshot of one subject
         * ("page:3", "project:7", "settings:identity", "menu:header", "collection:services").
         * The live version of a subject is its highest version; restoring copies an old snapshot back
         * into the working tables as a draft.
         */
        Schema::create('content_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('subject', 64);
            $table->unsignedInteger('version');
            $table->longText('snapshot');
            $table->char('hash', 40);
            $table->string('note')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['subject', 'version']);
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->string('email', 190);
            $table->string('project_type', 120)->nullable();
            $table->text('message')->nullable();
            $table->string('locale', 5);
            $table->string('status', 16)->default('new');           // new | read | in_progress | archived
            $table->uuid('submission_key')->unique();                // one row per form render: double clicks never duplicate
            $table->char('ip_hash', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('notification_status', 12)->default('pending'); // pending | sent | skipped | failed
            $table->text('notification_error')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 40);
            $table->string('subject', 64)->nullable()->index();
            $table->string('description');
            $table->json('properties')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('content_revisions');
    }
};
