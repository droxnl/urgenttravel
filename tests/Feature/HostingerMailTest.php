<?php

namespace Tests\Feature;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HostingerMailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_verify_a_hostinger_mailbox(): void
    {
        SiteSetting::put('hostinger_mail_api_token', 'hostinger-token');

        Http::fake([
            'api.mail.hostinger.com/api/v1/me' => Http::response([
                'data' => [
                    'orderResourceId' => 'ORDER-1',
                    'mailboxes' => [
                        [
                            'resourceId' => 'AC123',
                            'address' => 'info@urgent-travelservices.nl',
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs(User::query()->firstOrFail())
            ->postJson('/api/admin/settings/test-mail')
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('mailboxes.0.resource_id', 'AC123')
            ->assertJsonPath('mailboxes.0.address', 'info@urgent-travelservices.nl');
    }

    public function test_admin_can_send_a_test_email(): void
    {
        SiteSetting::put('mail_enabled', 1);
        SiteSetting::put('hostinger_mail_api_token', 'hostinger-token');
        SiteSetting::put('hostinger_mailbox_resource_id', 'AC123');
        SiteSetting::put('mail_sender_email', 'info@urgent-travelservices.nl');

        Http::fake([
            'api.mail.hostinger.com/api/v1/mailboxes/AC123/send' => Http::response(null, 202),
        ]);

        $this->actingAs(User::query()->firstOrFail())
            ->postJson('/api/admin/settings/send-test-mail', [
                'recipient' => 'owner@urgent-travelservices.nl',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Test email sent to owner@urgent-travelservices.nl.');

        Http::assertSent(fn ($request) => $request['to'] === ['owner@urgent-travelservices.nl']
            && $request['displayName'] === 'Urgent Travel Services');
    }
}
