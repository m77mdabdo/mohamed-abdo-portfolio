<?php

namespace App\Console\Commands;

use App\Content\Site;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Checks that this installation is ready to be public. Errors (✗) must be fixed before going live;
 * warnings (!) are content or operations still to do. Exit code 1 when any error is found, so a deploy
 * script can stop on it. Nothing is changed and no secret is printed.
 */
class Preflight extends Command
{
    protected $signature = 'portfolio:preflight';

    protected $description = 'Check that the site is ready for production (settings, database, mail, files, owner)';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $rows = [];

    private int $errors = 0;

    public function handle(): int
    {
        $url = (string) config('app.url');

        $this->check(app()->isProduction(), 'APP_ENV is production', 'Set APP_ENV=production.');
        $this->check(! config('app.debug'), 'APP_DEBUG is off', 'Set APP_DEBUG=false: debug pages show settings and code to visitors.');
        $this->check(filled(config('app.key')), 'APP_KEY is set', 'Run php artisan key:generate once on the server.');
        $this->check(str_starts_with($url, 'https://') && ! preg_match('#//(localhost|127\.0\.0\.1)#', $url), 'APP_URL is the public https address', 'Set APP_URL to the real https:// address of the site.');
        $this->check(config('session.secure') === true, 'Session cookie is https-only', 'Set SESSION_SECURE_COOKIE=true.');
        $this->check(! in_array(config('mail.default'), ['log', 'array'], true), 'A real mailer is configured', 'Set MAIL_MAILER=smtp and MAIL_*: sign-in codes are e-mailed, without mail nobody can sign in.');
        $this->check(! str_ends_with((string) config('mail.from.address'), '@localhost'), 'Sender address is real', 'Set MAIL_FROM_ADDRESS to an address of your domain.');
        $this->check(config('logging.channels.single.level') !== 'debug', 'Log level is not debug', 'Set LOG_LEVEL=warning.', warnOnly: true);

        try {
            DB::connection()->getPdo();
            $this->check(true, 'Database connection works', '');
            $pending = collect(File::files(database_path('migrations')))->map(fn ($f) => $f->getFilenameWithoutExtension())
                ->diff(DB::table('migrations')->pluck('migration'));
            $this->check($pending->isEmpty(), 'All migrations ran', 'Run php artisan migrate --database=migrate --force ('.$pending->count().' pending).');
            $this->check(User::query()->where('role', Role::Owner->value)->where('is_active', true)->exists(), 'An active owner account exists', 'Run php artisan portfolio:create-owner.');
            $this->check(! User::query()->where('email', 'like', '%@portfolio.test')->exists(), 'No test accounts left', 'Delete the *@portfolio.test accounts in Users.');
            $site = app(Site::class);
            $this->check((bool) ($site->settings('seo')['indexable'] ?? true), 'Search engines may index the site', 'Settings › SEO: allow indexing.', warnOnly: true);
            $this->check(filled($site->settings('cv')['file'] ?? null), 'A CV file is published', 'Settings › CV: upload the PDF and publish.', warnOnly: true);
        } catch (Throwable $e) {
            $this->check(false, 'Database connection works', 'Check DB_* in .env ('.class_basename($e).').');
        }

        $this->check(is_link(public_path('storage')) || is_dir(public_path('storage')), 'public/storage link exists', 'Run php artisan storage:link.');
        $this->check(is_file(public_path('build/manifest.json')), 'Built assets are present', 'Run npm ci && npm run build, or upload public/build.');
        $this->check(is_writable(storage_path()) && is_writable(base_path('bootstrap/cache')), 'storage and bootstrap/cache are writable', 'Give the web server write access to storage/ and bootstrap/cache/.');
        $this->check(app()->configurationIsCached(), 'Configuration is cached', 'Run php artisan optimize after every deploy.', warnOnly: true);
        $this->check(extension_loaded('gd'), 'PHP GD is available (image copies)', 'Enable the PHP gd extension.');
        $backups = glob(config('portfolio.backup_path').'/db-*.sql.gz') ?: [];
        $this->check($backups !== [] && filemtime(max($backups)) > now()->subDays(2)->getTimestamp(), 'A database backup from the last 2 days exists', 'Add the cron entry (schedule:run) so the daily backup runs.', warnOnly: true);

        $this->table(['', 'Check', 'To do'], $this->rows);
        $this->errors === 0
            ? $this->info('Ready for production.')
            : $this->error($this->errors.' problem(s) must be fixed before going live.');

        return $this->errors === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function check(bool $ok, string $label, string $fix, bool $warnOnly = false): void
    {
        if (! $ok && ! $warnOnly) {
            $this->errors++;
        }
        $this->rows[] = [$ok ? '✓' : ($warnOnly ? '!' : '✗'), $label, $ok ? '' : $fix];
    }
}
