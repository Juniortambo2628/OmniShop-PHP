<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public Order $order;
    public string $templateType;
    protected string $templateSubject;
    protected string $templateBody;

    public function __construct(Order $order, string $templateType = 'confirmation')
    {
        $this->order = $order;
        $this->templateType = $templateType;

        // Load the email template from settings
        $subjectKey = $templateType === 'invoice'
            ? 'email_template_invoice_subject'
            : 'email_template_order_confirmation_subject';

        $bodyKey = $templateType === 'invoice'
            ? 'email_template_invoice_body'
            : 'email_template_order_confirmation_body';

        $this->templateSubject = Setting::where('key', $subjectKey)->value('value')
            ?? 'Order Confirmation - ' . $order->order_id;

        $this->templateBody = Setting::where('key', $bodyKey)->value('value')
            ?? '<p>Thank you for your order <b>{{order_id}}</b>.</p>';
    }

    public function envelope(): Envelope
    {
        $subject = $this->replacePlaceholders($this->templateSubject);

        return new Envelope(
            subject: $subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: $this->buildHtmlBody(),
        );
    }

    public function attachments(): array
    {
        if ($this->templateType === 'invoice') {
            $pdf = Pdf::loadView('pdf.invoice', ['order' => $this->order]);
            return [
                Attachment::fromData(fn () => $pdf->output(), "Invoice-{$this->order->order_id}.pdf")
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }

    protected function buildHtmlBody(): string
    {
        $body = $this->replacePlaceholders($this->templateBody);

        // Wrap in a basic responsive email layout
        return <<<HTML
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; margin: 0; padding: 0; background: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; padding: 40px 20px; }
                .card { background: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
                .footer { text-align: center; padding: 20px; color: #999; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="card">
                    {$body}
                </div>
                <div class="footer">
                    OmniShop Limited &copy; 2026
                </div>
            </div>
        </body>
        </html>
        HTML;
    }

    protected function replacePlaceholders(string $text): string
    {
        $event = config("events.{$this->order->event_slug}");
        $invoiceLink = url("/admin/orders/{$this->order->order_id}/invoice");

        $replacements = [
            '{{order_id}}'      => $this->order->order_id,
            '{{company_name}}'  => $this->order->company_name,
            '{{contact_name}}'  => $this->order->contact_name,
            '{{total_amount}}'  => '$' . number_format($this->order->total, 2),
            '{{booth_number}}'  => $this->order->booth_number,
            '{{invoice_link}}'  => $invoiceLink,
            '{{event_name}}'    => $event['short_name'] ?? $this->order->event_slug,
            '{{email}}'         => $this->order->email,
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}
