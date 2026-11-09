<?php

namespace App\Console\Commands;

use App\Content\Site;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

/** Sends one test e-mail to the address that receives contact notices, to confirm the mail settings work. */
class MailTest extends Command
{
    protected $signature = 'portfolio:mail-test {to? : Address to send to (default: Settings › Contact inbox, else the public e-mail)}';

    protected $description = 'Send a test e-mail with the current mail settings';

    public function handle(Site $site): int
    {
        $to = $this->argument('to') ?: (($site->settings('contact')['recipient'] ?? '') ?: ($site->settings('identity')['email'] ?? ''));
        if (! $to) {
            $this->error('No recipient. Pass an address or set Settings › Contact inbox.');

            return self::FAILURE;
        }
        $mailer = config('mail.default');
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("MAIL_MAILER is \"{$mailer}\": nothing is delivered. Run: bash scripts/configure-mail.sh");

            return self::FAILURE;
        }
        try {
            Mail::raw("This is a test from your portfolio site.\nIf you can read this, contact-form notices will reach you.", fn ($m) => $m->to($to)->subject('Portfolio: mail settings work'));
        } catch (Throwable $e) {
            $this->error('Sending failed: '.$e->getMessage());

            return self::FAILURE;
        }
        $this->info("Test e-mail sent to {$to} via {$mailer}.");

        return self::SUCCESS;
    }
}
