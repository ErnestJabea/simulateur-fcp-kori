<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param string $type          'appointment' ou 'call'
     * @param string $leadName      Nom du prospect
     * @param string $leadEmail     Email du prospect
     * @param string $leadPhone     Téléphone du prospect
     * @param bool   $whatsapp      Le prospect accepte WhatsApp ?
     * @param string $fundName      Nom du fonds sélectionné
     * @param float  $initial       Apport initial (FCFA)
     * @param float  $periodic      Versement périodique (FCFA)
     * @param float  $duration      Durée en années
     * @param float  $finalBalance  Capital projeté final (FCFA)
     */
    public function __construct(
        public readonly string $type,
        public readonly string $leadName,
        public readonly string $leadEmail,
        public readonly string $leadPhone,
        public readonly bool   $whatsapp,
        public readonly string $fundName,
        public readonly float  $initial,
        public readonly float  $periodic,
        public readonly float  $duration,
        public readonly float  $finalBalance,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->type === 'appointment'
            ? '📅 Nouvelle demande de rendez-vous – ' . $this->leadName
            : '📞 Nouvelle demande de rappel – ' . $this->leadName;

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.contact-request',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
