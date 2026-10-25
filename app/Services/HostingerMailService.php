<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HostingerMailService
{
    public function enabled(): bool
    {
        return filter_var(SiteSetting::get('mail_enabled', false), FILTER_VALIDATE_BOOL);
    }

    public function isConfigured(): bool
    {
        return $this->enabled()
            && filled($this->token())
            && filled($this->mailboxResourceId())
            && filled($this->senderEmail());
    }

    public function account(): array
    {
        $response = $this->client()->get('me');
        $response->throw();

        return [
            'order_resource_id' => $response->json('data.orderResourceId'),
            'mailboxes' => collect($response->json('data.mailboxes', []))
                ->map(fn (array $mailbox) => [
                    'resource_id' => $mailbox['resourceId'] ?? null,
                    'address' => $mailbox['address'] ?? null,
                ])
                ->filter(fn (array $mailbox) => $mailbox['resource_id'])
                ->values()
                ->all(),
        ];
    }

    public function send(
        string|array $to,
        string $subject,
        string $text,
        string $html,
        array $cc = [],
        array $bcc = []
    ): void {
        if (! $this->isConfigured()) {
            throw new RuntimeException('Hostinger Mail API is not fully configured or is disabled.');
        }

        $response = $this->client()->post(
            'mailboxes/'.rawurlencode((string) $this->mailboxResourceId()).'/send',
            [
                'to' => array_values((array) $to),
                'displayName' => SiteSetting::get('mail_sender_name', 'Urgent Travel Services'),
                'cc' => array_values($cc),
                'bcc' => array_values($bcc),
                'subject' => $subject,
                'text' => $text,
                'html' => $html,
            ]
        );
        $response->throw();
    }

    public function senderEmail(): ?string
    {
        return SiteSetting::get('mail_sender_email') ?: SiteSetting::get('email');
    }

    private function client(): PendingRequest
    {
        $token = $this->token();
        if (blank($token)) {
            throw new RuntimeException('Add a Hostinger Mail API token before connecting.');
        }

        return Http::withToken($token)
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->baseUrl(rtrim($this->baseUrl(), '/').'/');
    }

    private function token(): ?string
    {
        return SiteSetting::get('hostinger_mail_api_token') ?: config('services.hostinger_mail.token');
    }

    private function mailboxResourceId(): ?string
    {
        return SiteSetting::get('hostinger_mailbox_resource_id')
            ?: config('services.hostinger_mail.mailbox_resource_id');
    }

    private function baseUrl(): string
    {
        return SiteSetting::get(
            'hostinger_mail_api_base_url',
            config('services.hostinger_mail.base_url', 'https://api.mail.hostinger.com/api/v1')
        );
    }
}
