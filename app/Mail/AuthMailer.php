<?php

declare(strict_types=1);

namespace JobVisa\App\Mail;

use JobVisa\App\Logging\Logger;

/**
 * Local development mail/log fallback (no SMTP required).
 */
final class AuthMailer
{
    /**
     * @param  array<string, mixed>  $context  Must not include raw secrets in production logs
     */
    public function send(string $to, string $subject, string $htmlBody, array $context = []): void
    {
        $driver = (string) config('mail.driver', 'log');
        $isProduction = (string) config('app.env', 'production') === 'production'
            && !(bool) config('app.debug', false);

        if ($isProduction) {
            unset($context['token'], $context['plain_token'], $context['reset_token'], $context['verification_token']);
            Logger::info('Auth mail queued', [
                'to' => $to,
                'subject' => $subject,
                'driver' => $driver,
            ] + $context);

            // SMTP not configured yet — intentionally no remote send.
            return;
        }

        $directory = base_path('storage/logs');

        if (!is_dir($directory)) {
            @mkdir($directory, 0755, true);
        }

        $file = $directory . DIRECTORY_SEPARATOR . 'mail-' . date('Y-m-d') . '.log';
        $entry = str_repeat('=', 72) . "\n"
            . 'Time: ' . date('c') . "\n"
            . 'To: ' . $to . "\n"
            . 'Subject: ' . $subject . "\n"
            . 'Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n\n"
            . $htmlBody . "\n\n";

        @file_put_contents($file, $entry, FILE_APPEND | LOCK_EX);

        Logger::info('Auth mail written to local log', [
            'to' => $to,
            'subject' => $subject,
            'file' => 'storage/logs/mail-' . date('Y-m-d') . '.log',
        ]);
    }
}
