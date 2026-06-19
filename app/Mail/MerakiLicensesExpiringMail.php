<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MerakiLicensesExpiringMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly array  $byOrg,
        public readonly array  $summary,
        public readonly string $generatedAt,
    ) {}

    public function envelope(): Envelope
    {
        $critical = $this->summary['critical'];

        $subject = $critical > 0
            ? "⚠️ Meraki: {$critical} licencia(s) vencen en menos de 30 días"
            : "Meraki: Reporte de licencias próximas a vencer";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.meraki-licenses-expiring');
    }
}
