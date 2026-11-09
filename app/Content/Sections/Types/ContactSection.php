<?php

namespace App\Content\Sections\Types;

use App\Content\Fields\Field;
use App\Content\Sections\SectionContext;
use App\Content\Sections\SectionData;
use App\Content\Sections\SectionType;
use Illuminate\Support\Str;

/** Contact block: big title, round button, e-mail, social links and the message form (saved to the inbox). */
class ContactSection extends SectionType
{
    public function key(): string
    {
        return 'contact';
    }

    public function label(): string
    {
        return __('Contact + form');
    }

    public function description(): string
    {
        return __('Title, e-mail, social links and the contact form.');
    }

    public function icon(): string
    {
        return 'heroicon-o-envelope';
    }

    public function defaultAnchor(): ?string
    {
        return 'contact';
    }

    public function fields(): array
    {
        return [
            $this->eyebrow(),
            Field::title('title', 'Title')->translatable(),
            Field::text('circle_label', 'Round button')->translatable()->max(30),
            Field::text('copy_label', '"Copy e-mail" button')->translatable()->max(30),
            Field::text('copied_label', 'After copying')->translatable()->max(30),
            Field::toggle('show_form', 'Show the form'),
            Field::text('name_label', 'Form: name')->translatable()->max(60),
            Field::text('email_label', 'Form: e-mail')->translatable()->max(60),
            Field::text('type_label', 'Form: project type')->translatable()->max(60),
            Field::text('message_label', 'Form: details')->translatable()->max(60),
            Field::items('project_types', 'Project types in the form', [
                Field::text('label', 'Project type')->translatable()->max(60),
            ], 10),
            Field::text('submit_label', 'Form: send button')->translatable()->max(40),
            Field::text('note', 'Note under the button')->translatable()->max(200),
            Field::text('success_message', 'Message after sending')->translatable()->max(200),
            Field::text('error_message', 'Message when sending fails')->translatable()->max(200),
        ];
    }

    public function prepare(SectionData $section, SectionContext $context): array
    {
        return [
            'identity' => $context->site->settings('identity'),
            'submissionKey' => (string) Str::uuid(),
        ];
    }
}
