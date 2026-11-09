<?php

namespace App\Http\Controllers;

use App\Content\Inbox\StoreContactMessage;
use App\Content\Site;
use App\Http\Requests\ContactMessageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Throwable;

/** Contact form endpoint. Works as a normal form post (no JavaScript) and as a fetch request. */
class ContactController extends Controller
{
    public function store(string $locale, ContactMessageRequest $request, StoreContactMessage $store, Site $site): JsonResponse|RedirectResponse
    {
        if ($request->isSpam()) {
            return $this->respond($request, true, $site->ui('form.success'));
        }

        try {
            $result = $store->handle($request->validated(), $locale, $request->ip(), $request->userAgent());
        } catch (Throwable $e) {
            report($e);

            return $this->respond($request, false, $site->ui('form.failure'), 500);
        }

        return $this->respond($request, true, $site->ui($result['created'] ? 'form.success' : 'form.already_sent'));
    }

    private function respond(ContactMessageRequest $request, bool $ok, string $message, int $status = 200): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => $ok, 'message' => $message], $status);
        }

        return redirect()->to(url()->previous().'#contact')->with('contact_status', ['ok' => $ok, 'message' => $message]);
    }
}
