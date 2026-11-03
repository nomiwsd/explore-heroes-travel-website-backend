<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * ST-01: send a plain test email and show the effective mail configuration.
 *
 *   php artisan mail:test you@example.com
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {to : Recipient email address}';

    protected $description = 'Send a plain-text test email using the active mailer and print the mail configuration';

    public function handle()
    {
        $to = (string) $this->argument('to');
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->error("Invalid email address: {$to}");
            return 1;
        }

        $mailer = (string) config('mail.default');
        $smtp = (array) config('mail.mailers.smtp', []);
        $this->table(['Setting', 'Value'], [
            ['mailer (mail.default)', $mailer],
            ['force_env (MAIL_FORCE_ENV)', config('mail.force_env') ? 'true' : 'false'],
            ['from', config('mail.from.name') . ' <' . config('mail.from.address') . '>'],
            ['smtp host', $smtp['host'] ?? ''],
            ['smtp port', $smtp['port'] ?? ''],
            ['smtp encryption', $smtp['encryption'] ?? ''],
            ['smtp username', $smtp['username'] ?? ''],
            ['smtp password', !empty($smtp['password']) ? '(set)' : '(empty)'],
        ]);

        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("Active mailer is '{$mailer}': the message will NOT be delivered (check storage/logs).");
        }

        $site = config('app.name') ?: 'Explore Heroes';
        try {
            Mail::raw(
                "This is a test email from {$site}.\n\nMailer: {$mailer}\nSent: " . now()->toDateTimeString(),
                function ($message) use ($to, $site) {
                    $message->to($to)->subject("[{$site}] Mail test");
                }
            );
        } catch (\Throwable $e) {
            $this->error('Sending failed: ' . $e->getMessage());
            return 1;
        }

        $this->info("Test email handed to the '{$mailer}' mailer for {$to}.");
        return 0;
    }
}
