<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Working copy of all editable content. What the dashboard saves lands here (the draft);
 * what visitors see comes from content_revisions (see the next migration).
 * Translatable fields are JSON maps keyed by locale: {"en": "...", "ar": "..."}.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->nullable()->unique(); // system pages: home, work, about, services, cv, contact
            $table->string('slug', 80)->unique();
            $table->json('title');
            $table->json('seo')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->unsignedInteger('lock_version')->default(0);
            $table->string('source_key')->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->string('uid', 26)->unique();        // stable id, survives reordering and publishing
            $table->string('type', 40);
            $table->string('preset', 40)->nullable();
            $table->string('anchor', 60)->nullable();   // in-page link target (#about)
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('schema_version')->default(1);
            $table->json('data');
            $table->timestamps();
            $table->index(['page_id', 'sort_order', 'id']);
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->json('name');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('technologies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('icon', 40)->nullable();      // key in the safe icon registry
            $table->string('color', 7)->nullable();
            $table->json('role')->nullable();            // caption on the tech cube
            $table->boolean('in_cube')->default(false);
            $table->unsignedTinyInteger('band_row')->nullable(); // 1-3: row in the moving skills band
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['in_cube', 'sort_order']);
            $table->index(['band_row', 'sort_order']);
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 80)->unique();
            $table->json('title');
            $table->json('kind');
            $table->json('status_label');
            $table->string('status_tone', 12)->default('neutral'); // live | neutral
            $table->string('year', 10)->nullable();
            $table->string('color', 7)->nullable();
            $table->json('summary');
            $table->json('brief')->nullable();
            $table->json('built')->nullable();
            $table->json('features')->nullable();        // [{"en": "...", "ar": "..."}]
            $table->json('client')->nullable();
            $table->json('role')->nullable();
            $table->json('languages')->nullable();
            $table->string('live_url')->nullable();
            $table->string('repo_url')->nullable();
            $table->foreignId('cover_media_id')->nullable()->constrained('media')->nullOnDelete();
            $table->foreignId('next_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('seo')->nullable();
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
            $table->index(['sort_order', 'id']);
            $table->index(['is_featured', 'sort_order']);
        });

        Schema::create('project_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('media_id')->constrained('media')->restrictOnDelete();
            $table->string('kind', 10); // desktop | mobile
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('caption')->nullable();
            $table->timestamps();
            $table->index(['project_id', 'kind', 'sort_order']);
        });

        Schema::create('category_project', function (Blueprint $table) {
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->primary(['category_id', 'project_id']);
        });

        Schema::create('project_technology', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('technology_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->unique(['project_id', 'technology_id']);
            $table->index(['project_id', 'sort_order']);
        });

        Schema::create('slug_redirects', function (Blueprint $table) {
            $table->id();
            $table->string('kind', 20);        // project | page
            $table->string('old_slug', 80);
            $table->unsignedBigInteger('target_id');
            $table->timestamps();
            $table->unique(['kind', 'old_slug']);
        });

        // Shared lists used by several sections and pages.
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('number', 6)->nullable();
            $table->json('title');
            $table->json('description');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->json('period');
            $table->json('role');
            $table->json('organization');
            $table->json('description')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('skill_groups', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('items'); // ["Laravel", "PHP 8", ...] — technology names, not translated
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('spoken_languages', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('level');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('process_steps', function (Blueprint $table) {
            $table->id();
            $table->json('title');
            $table->json('description');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name', 120);
            $table->json('role')->nullable();
            $table->json('quote');
            $table->boolean('has_consent')->default(false); // shown on the site only with the client's consent
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('stats', function (Blueprint $table) {
            $table->id();
            $table->string('value', 20);
            $table->json('label');
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('group', 40)->unique();
            $table->json('data');
            $table->unsignedInteger('lock_version')->default(0);
            $table->timestamps();
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique(); // header | drawer | footer
            $table->string('name', 80);
            $table->timestamps();
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->cascadeOnDelete();
            $table->json('label');
            $table->string('link_type', 12);   // page | section | project | url | email | cv
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('section_anchor', 60)->nullable();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url')->nullable();
            $table->boolean('new_tab')->default(false);
            $table->boolean('is_visible')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->index(['menu_id', 'parent_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        foreach (['menu_items', 'menus', 'site_settings', 'stats', 'testimonials', 'process_steps', 'spoken_languages',
            'skill_groups', 'experiences', 'services', 'slug_redirects', 'project_technology', 'category_project',
            'project_media', 'projects', 'technologies', 'categories', 'page_sections', 'pages'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
