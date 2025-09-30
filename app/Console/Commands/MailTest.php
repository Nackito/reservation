<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class MailTest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * Usage: php artisan mail:test user@example.com --subject="Sujet" --text="Corps du message"
     */
    protected $signature = 'mail:test {to : Adresse du destinataire}
                            {--subject=Test Afridayz SMTP OVH : Sujet de l\'email}
                            {--text=Ceci est un email de test envoyé depuis Afridayz. : Corps du message}';

    /**
     * The console command description.
     */
    protected $description = 'Envoie un email de test en utilisant la configuration mail courante (ex: SMTP OVH)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $to = (string) $this->argument('to');
        $subject = (string) $this->option('subject');
        $text = (string) $this->option('text');

        $defaultMailer = config('mail.default');
        $smtpHost = config('mail.mailers.smtp.host');
        $smtpPort = config('mail.mailers.smtp.port');

        $this->info("Mailer: {$defaultMailer}");
        if ($smtpHost) {
            $this->info("SMTP: {$smtpHost}:{$smtpPort}");
        }

        try {
            Mail::raw($text, function ($m) use ($to, $subject) {
                $m->to($to)->subject($subject);
            });
            $this->info("Email de test envoyé à {$to}.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Échec de l\'envoi: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
