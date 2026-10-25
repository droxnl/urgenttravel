<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Services\BookingNotifier;
use App\Services\HostingerMailService;
use App\Services\OpenAiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SettingsController extends Controller
{
    private const ALLOWED = [
        'site_name', 'site_tagline', 'phone', 'whatsapp', 'email',
        'address', 'city', 'postcode', 'country', 'kvk', 'vat', 'booking_url',
        'mail_enabled', 'hostinger_mail_api_token', 'hostinger_mailbox_resource_id',
        'mail_sender_name', 'mail_sender_email', 'mail_notification_email',
        'mail_send_admin', 'mail_send_customer',
        'openai_enabled', 'openai_api_key', 'openai_model', 'openai_base_url',
        'openai_language', 'openai_tone', 'openai_system_prompt', 'openai_auto_seo',
    ];

    public function show(OpenAiService $openAi, HostingerMailService $mailer): JsonResponse
    {
        return response()->json([
            'settings' => SiteSetting::allValues(),
            'openai_configured' => $openAi->isConfigured(),
            'mail_configured' => $mailer->isConfigured(),
            'mail_credentials_saved' => filled(SiteSetting::get('hostinger_mail_api_token') ?: config('services.hostinger_mail.token')),
        ]);
    }

    public function update(Request $request, HostingerMailService $mailer): JsonResponse
    {
        $validated = $request->validate([
            'site_name' => ['sometimes', 'string', 'max:190'],
            'site_tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'whatsapp' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'nullable', 'email', 'max:190'],
            'address' => ['sometimes', 'nullable', 'string', 'max:190'],
            'city' => ['sometimes', 'nullable', 'string', 'max:100'],
            'postcode' => ['sometimes', 'nullable', 'string', 'max:20'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'kvk' => ['sometimes', 'nullable', 'string', 'max:30'],
            'vat' => ['sometimes', 'nullable', 'string', 'max:30'],
            'booking_url' => ['sometimes', 'nullable', 'string', 'max:190'],
            'mail_enabled' => ['sometimes', 'boolean'],
            'hostinger_mail_api_token' => ['sometimes', 'nullable', 'string', 'max:500'],
            'hostinger_mailbox_resource_id' => ['sometimes', 'nullable', 'string', 'max:190'],
            'mail_sender_name' => ['sometimes', 'string', 'max:190'],
            'mail_sender_email' => ['sometimes', 'nullable', 'email:rfc', 'max:190'],
            'mail_notification_email' => ['sometimes', 'nullable', 'email:rfc', 'max:190'],
            'mail_send_admin' => ['sometimes', 'boolean'],
            'mail_send_customer' => ['sometimes', 'boolean'],
            'openai_enabled' => ['sometimes', 'boolean'],
            'openai_api_key' => ['sometimes', 'nullable', 'string', 'max:500'],
            'openai_model' => ['sometimes', 'string', 'max:100'],
            'openai_base_url' => ['sometimes', 'url', 'max:190'],
            'openai_language' => ['sometimes', 'in:nl,en'],
            'openai_tone' => ['sometimes', 'string', 'max:255'],
            'openai_system_prompt' => ['sometimes', 'string', 'max:5000'],
            'openai_auto_seo' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('openai_api_key', $validated) && blank($validated['openai_api_key'])) {
            unset($validated['openai_api_key']);
        }
        if (array_key_exists('hostinger_mail_api_token', $validated)) {
            $validated['hostinger_mail_api_token'] = trim((string) $validated['hostinger_mail_api_token']);
            if (blank($validated['hostinger_mail_api_token'])) {
                unset($validated['hostinger_mail_api_token']);
            }
        }

        $newMailToken = $validated['hostinger_mail_api_token'] ?? null;
        $resourceId = $validated['hostinger_mailbox_resource_id'] ?? SiteSetting::get('hostinger_mailbox_resource_id');
        if (filled($newMailToken) && blank($resourceId)) {
            try {
                $account = $mailer->account($newMailToken);
                $mailboxes = collect($account['mailboxes'] ?? []);
                $senderEmail = $validated['mail_sender_email']
                    ?? SiteSetting::get('mail_sender_email')
                    ?? SiteSetting::get('email');
                $mailbox = $mailboxes->first(
                    fn (array $item) => mb_strtolower((string) ($item['address'] ?? '')) === mb_strtolower((string) $senderEmail)
                ) ?? $mailboxes->first();

                if (! $mailbox) {
                    return response()->json([
                        'message' => 'The Hostinger token connected successfully, but no sender mailbox was returned.',
                    ], 422);
                }

                $validated['hostinger_mailbox_resource_id'] = $mailbox['resource_id'];
                if (blank($validated['mail_sender_email'] ?? null)) {
                    $validated['mail_sender_email'] = $mailbox['address'];
                }
            } catch (Throwable $exception) {
                return response()->json([
                    'message' => 'The Hostinger mailbox could not be detected: '.$exception->getMessage(),
                ], 422);
            }
        }

        foreach ($validated as $key => $value) {
            if (in_array($key, self::ALLOWED, true)) {
                SiteSetting::put($key, is_bool($value) ? (int) $value : $value);
            }
        }

        return response()->json([
            'message' => 'Settings saved.',
            'settings' => SiteSetting::allValues(),
            'mail_configured' => $mailer->isConfigured(),
            'mail_credentials_saved' => filled(SiteSetting::get('hostinger_mail_api_token') ?: config('services.hostinger_mail.token')),
        ]);
    }

    public function testOpenAi(OpenAiService $openAi): JsonResponse
    {
        try {
            return response()->json($openAi->testConnection());
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Connection failed: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function testMail(Request $request, HostingerMailService $mailer): JsonResponse
    {
        $validated = $request->validate([
            'hostinger_mail_api_token' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            return response()->json([
                'ok' => true,
                ...$mailer->account($validated['hostinger_mail_api_token'] ?? null),
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Hostinger Mail API connection failed: '.$exception->getMessage(),
            ], 422);
        }
    }

    public function sendTestMail(Request $request, BookingNotifier $notifier): JsonResponse
    {
        $validated = $request->validate([
            'recipient' => ['required', 'email:rfc', 'max:190'],
        ]);

        try {
            $notifier->sendTest($validated['recipient']);

            return response()->json([
                'message' => 'Test email sent to '.$validated['recipient'].'.',
            ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Test email could not be sent: '.$exception->getMessage(),
            ], 422);
        }
    }
}
