<?php

namespace App\Http\Controllers;

use App\Content\Site;
use App\Support\Tr;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * CV download. Without an uploaded PDF this never answers 404: it returns to the résumé section
 * with a clear note instead.
 */
class CvController extends Controller
{
    public function __invoke(string $locale, Site $site, Request $request): StreamedResponse|RedirectResponse|BinaryFileResponse
    {
        $file = $site->cvFile();
        $disk = $file ? Storage::disk($file->disk) : null;
        if (! $file || ! $disk->exists($file->path)) {
            $cvPage = $site->page('cv');
            $target = $cvPage ? $site->pageUrl($cvPage, $locale) : route('home', ['locale' => $locale]);

            return redirect()->to($target.'#cv')->with('cv_missing', true);
        }

        $identity = $site->settings('identity');
        $name = str(Tr::pick($identity['name'] ?? 'CV', 'en'))->slug()->append('-CV.pdf')->toString();

        if ($request->boolean('view')) {
            // opens in the browser's PDF viewer
            return response()->file($disk->path($file->path), ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$name.'"', 'X-Content-Type-Options' => 'nosniff']);
        }

        return $disk->download($file->path, $name, ['Content-Type' => 'application/pdf', 'X-Content-Type-Options' => 'nosniff']);
    }
}
