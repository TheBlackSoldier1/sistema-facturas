<?php

namespace App\Mail;

use App\Models\Factura;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class InvoiceAssigned extends Mailable
{
    public function __construct(
        public Factura $factura,
        public string $areaName,
        public string $instructions,
        private string $pdf,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: 'Factura #'.$this->factura->id.' asignada para pago');
    }

    public function content(): Content
    {
        return new Content(view: 'mail.invoice-assigned');
    }

    public function attachments(): array
    {
        return [Attachment::fromData(fn () => $this->pdf, 'factura-'.$this->factura->id.'.pdf')->withMime('application/pdf')];
    }
}
