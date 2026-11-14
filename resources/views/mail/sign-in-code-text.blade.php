{{ __('Hello :name,', ['name' => $name], $locale) }}

{{ __('Use this code to finish signing in to the dashboard:', locale: $locale) }}

{{ $code }}

{{ __('The code works once and expires in :minutes minutes.', ['minutes' => $minutes], $locale) }}
{{ __('If you did not try to sign in, ignore this e-mail and change your password.', locale: $locale) }}
