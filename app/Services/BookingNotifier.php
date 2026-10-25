<?php

namespace App\Services;

use App\Models\BookingInquiry;
use App\Models\EmailDelivery;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\View;
use Throwable;

class BookingNotifier
{
    public function __construct(private readonly HostingerMailService $mailer) {}

    public function notify(BookingInquiry $inquiry): void
    {
        if (! $this->mailer->isConfigured()) {
            return;
        }

        $data = $this->emailData($inquiry);
        $dutch = $inquiry->locale === 'nl';

        if (filter_var(SiteSetting::get('mail_send_admin', true), FILTER_VALIDATE_BOOL)) {
            $recipient = SiteSetting::get('mail_notification_email') ?: SiteSetting::get('email');
            if ($recipient) {
                $this->deliver(
                    $inquiry,
                    'admin_booking',
                    $recipient,
                    $dutch
                        ? ($inquiry->type === 'contact'
                            ? "Nieuw contactbericht {$inquiry->reference}"
                            : "Nieuwe transferaanvraag {$inquiry->reference}")
                        : ($inquiry->type === 'contact'
                            ? "New contact enquiry {$inquiry->reference}"
                            : "New transfer request {$inquiry->reference}"),
                    $dutch ? 'mail.booking-admin-nl' : 'mail.booking-admin',
                    $data
                );
            }
        }

        if (
            filter_var(SiteSetting::get('mail_send_customer', true), FILTER_VALIDATE_BOOL)
            && filter_var($inquiry->email, FILTER_VALIDATE_EMAIL)
        ) {
            $this->deliver(
                $inquiry,
                'customer_confirmation',
                $inquiry->email,
                $dutch
                    ? ($inquiry->type === 'contact'
                        ? "Wij hebben uw bericht ontvangen {$inquiry->reference}"
                        : "Wij hebben uw transferaanvraag ontvangen {$inquiry->reference}")
                    : ($inquiry->type === 'contact'
                        ? "We received your message {$inquiry->reference}"
                        : "We received your transfer request {$inquiry->reference}"),
                $dutch ? 'mail.booking-customer-nl' : 'mail.booking-customer',
                $data
            );
        }
    }

    public function sendTest(string $recipient): void
    {
        $html = View::make('mail.test', [
            'recipient' => $recipient,
            'senderName' => SiteSetting::get('mail_sender_name', 'Urgent Travel Services'),
            'logoUrl' => url('images/brand/urgent-travel-services-logo.png'),
        ])->render();

        $this->mailer->send(
            $recipient,
            'Hostinger Mail API connection confirmed',
            $this->plainText($html),
            $html
        );
    }

    private function deliver(
        BookingInquiry $inquiry,
        string $type,
        string $recipient,
        string $subject,
        string $view,
        array $data
    ): void {
        $delivery = EmailDelivery::query()->create([
            'booking_inquiry_id' => $inquiry->id,
            'provider' => 'hostinger_mail_api',
            'type' => $type,
            'recipient' => $recipient,
            'subject' => $subject,
            'status' => 'queued',
        ]);

        try {
            $html = View::make($view, $data)->render();
            $this->mailer->send($recipient, $subject, $this->plainText($html), $html);
            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'error' => null,
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $delivery->update([
                'status' => 'failed',
                'error' => mb_substr($exception->getMessage(), 0, 2000),
            ]);
        }
    }

    private function emailData(BookingInquiry $inquiry): array
    {
        return [
            'inquiry' => $inquiry,
            'senderName' => SiteSetting::get('mail_sender_name', 'Urgent Travel Services'),
            'senderEmail' => SiteSetting::get('mail_sender_email') ?: SiteSetting::get('email'),
            'contactPhone' => SiteSetting::get('phone'),
            'contactEmail' => SiteSetting::get('email'),
            'adminUrl' => url('/admin/inquiries'),
            'logoUrl' => url('images/brand/urgent-travel-services-logo.png'),
        ];
    }

    private function plainText(string $html): string
    {
        $text = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;
        $text = preg_replace('/<\/p>|<\/tr>|<\/h[1-6]>/i', "\n", $text) ?? $text;

        return trim(html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }
}
