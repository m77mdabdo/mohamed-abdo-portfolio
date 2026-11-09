New message from the portfolio contact form

Name: {{ $contact->name }}
E-mail: {{ $contact->email }}
Project type: {{ $contact->project_type ?: '—' }}
Language: {{ $contact->locale }}
Received: {{ $contact->created_at?->timezone('Asia/Riyadh')->format('Y-m-d H:i') }}

{{ $contact->message ?: '(no details)' }}

Open in the dashboard: {{ $url }}
