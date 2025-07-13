<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VinylImportCompleted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $totalDispatched;
    public $totalProcessed;
    public $totalUploaded;
    public $startTime;
    public $endTime;
    public $errors;

    public function __construct($totalDispatched, $totalProcessed, $totalUploaded, $startTime, $endTime, $errors = [])
    {
        $this->totalDispatched = $totalDispatched;
        $this->totalProcessed = $totalProcessed;
        $this->totalUploaded = $totalUploaded;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
        $this->errors = $errors;
    }

    public function envelope(): Envelope
    {
        $status = $this->totalUploaded > 0 ? '✅' : '❌';
        return new Envelope(
            subject: "{$status} Import Vinyls terminé - {$this->totalUploaded}/{$this->totalDispatched} images uploadées",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.vinyl-import-completed',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
