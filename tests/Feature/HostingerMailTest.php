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

    public function test_admin_can_verify_an_unsaved_token_and_discover_its_mailbox(): void
    {
        Http::fake([
            'api.mail.hostinger.com/api/v1/me' => Http::response([
                'data' => [
                    'orderResourceId' => 'ORDER-1',
                    'mailboxes' => [
                        ['resourceId' => 'AC456', 'address' => 'bookings@urgent-travelservices.nl'],
                    ],
                ],
            ]),
        ]);

        $this->actingAs(User::query()->firstOrFail())
            ->postJson('/api/admin/settings/test-mail', [
                'hostinger_mail_api_token' => 'new-unsaved-token',
            ])
            ->assertOk()
            ->assertJsonPath('mailboxes.0.resource_id', 'AC456')
            ->assertJsonPath('mailboxes.0.address', 'bookings@urgent-travelservices.nl');

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer new-unsaved-token'));
    }

    public function test_new_token_automatically_saves_the_discovered_mailbox_resource_id(): void
    {
        Http::fake([
            'api.mail.hostinger.com/api/v1/me' => Http::response([
                'data' => [
                    'orderResourceId' => 'ORDER-1',
                    'mailboxes' => [
                        ['resourceId' => 'AC789', 'address' => 'info@urgent-travelservices.nl'],
                    ],
                ],
            ]),
        ]);

        $this->actingAs(User::query()->firstOrFail())
            ->putJson('/api/admin/settings', [
                'hostinger_mail_api_token' => 'new-hostinger-token',
                'hostinger_mailbox_resource_id' => '',
            ])
            ->assertOk()
            ->assertJsonPath('settings.hostinger_mailbox_resource_id', 'AC789');

        $this->assertDatabaseHas('site_settings', [
            'key' => 'hostinger_mailbox_resource_id',
            'value' => 'AC789',
        ]);
        $this->assertDatabaseMissing('site_settings', [
            'key' => 'hostinger_mail_api_token',
            'value' => 'new-hostinger-token',
        ]);
        $this->assertSame('new-hostinger-token', SiteSetting::get('hostinger_mail_api_token'));
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
