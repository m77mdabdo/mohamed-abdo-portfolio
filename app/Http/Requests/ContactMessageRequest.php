<?php

namespace App\Http\Requests;

use App\Content\Site;
use Illuminate\Foundation\Http\FormRequest;

class ContactMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => trim((string) $this->input('email')),
            'project_type' => trim((string) $this->input('project_type')) ?: null,
            'message' => trim(str_replace("\r\n", "\n", (string) $this->input('message'))) ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:190'],
            'project_type' => ['nullable', 'string', 'max:120'],
            'message' => ['nullable', 'string', 'max:5000'],
            'submission_key' => ['required', 'uuid'],
            'website' => ['nullable', 'string', 'max:500'], // honeypot: real people never see it; filled = spam, dropped in the controller
        ];
    }

    public function messages(): array
    {
        $site = app(Site::class);

        return [
            'name.required' => $site->ui('form.required'),
            'email.required' => $site->ui('form.required'),
            'email.email' => $site->ui('form.invalid_email'),
            '*.max' => $site->ui('form.too_long'),
            'submission_key.*' => $site->ui('form.failure'),
        ];
    }

    public function isSpam(): bool
    {
        return filled($this->input('website'));
    }
}
