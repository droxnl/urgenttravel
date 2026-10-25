<?php

namespace Tests\Feature;

use App\Models\BookingInquiry;
use App\Models\EmailDelivery;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BookingInquiryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_a_website_visitor_can_submit_a_transfer_request(): void
    {
        $response = $this->postJson('/api/inquiries', [
            'type' => 'booking',
            'name' => 'Alex Example',
            'email' => 'alex@example.org',
            'phone' => '+31612345678',
            'pickup' => 'Amsterdam Centraal',
            'dropoff' => 'Schiphol Airport',
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_time' => '08:30',
            'passengers' => 2,
            'luggage' => 3,
            'service_type' => 'Airport transfer',
            'source_page' => '/booking/',
            'consent' => true,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['message', 'reference', 'status']);

        $this->assertDatabaseHas(BookingInquiry::class, [
            'email' => 'alex@example.org',
            'pickup' => 'Amsterdam Centraal',
            'dropoff' => 'Schiphol Airport',
            'status' => 'new',
        ]);
    }

    public function test_a_request_requires_contact_details_and_consent(): void
    {
        $this->postJson('/api/inquiries', [
            'name' => 'No Details',
            'consent' => false,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone', 'consent']);
    }

    public function test_a_booking_sends_admin_and_customer_emails_through_hostinger(): void
    {
        SiteSetting::put('mail_enabled', 1);
        SiteSetting::put('hostinger_mail_api_token', 'hostinger-secret-token');
        SiteSetting::put('hostinger_mailbox_resource_id', 'AC123');
        SiteSetting::put('mail_sender_email', 'info@urgent-travelservices.nl');
        SiteSetting::put('mail_notification_email', 'bookings@urgent-travelservices.nl');
        SiteSetting::put('mail_send_admin', 1);
        SiteSetting::put('mail_send_customer', 1);

        Http::fake([
            'api.mail.hostinger.com/api/v1/mailboxes/AC123/send' => Http::response(null, 202),
        ]);

        $this->postJson('/api/inquiries', [
            'type' => 'booking',
            'name' => 'Jamie Traveller',
            'email' => 'jamie@example.org',
            'phone' => '+31612345678',
            'pickup' => 'Lelystad',
            'dropoff' => 'Schiphol Airport',
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_time' => '07:15',
            'passengers' => 2,
            'luggage' => 2,
            'service_type' => 'Airport transfer',
            'source_page' => '/booking/',
            'consent' => true,
        ])->assertCreated();

        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer hostinger-secret-token')
            && $request['to'] === ['bookings@urgent-travelservices.nl']
            && str_contains($request['subject'], 'New transfer request'));
        Http::assertSent(fn ($request) => $request['to'] === ['jamie@example.org']
            && str_contains($request['subject'], 'We received your transfer request'));

        $this->assertSame(2, EmailDelivery::query()->where('status', 'sent')->count());
        $this->assertDatabaseHas('email_deliveries', [
            'type' => 'customer_confirmation',
            'recipient' => 'jamie@example.org',
            'status' => 'sent',
        ]);
    }

    public function test_a_dutch_request_sends_dutch_confirmation_emails(): void
    {
        SiteSetting::put('mail_enabled', 1);
        SiteSetting::put('hostinger_mail_api_token', 'hostinger-secret-token');
        SiteSetting::put('hostinger_mailbox_resource_id', 'AC123');
        SiteSetting::put('mail_sender_email', 'info@urgent-travelservices.nl');
        SiteSetting::put('mail_notification_email', 'bookings@urgent-travelservices.nl');

        Http::fake([
            'api.mail.hostinger.com/api/v1/mailboxes/AC123/send' => Http::response(null, 202),
        ]);

        $this->postJson('/api/inquiries', [
            'type' => 'booking',
            'name' => 'Jan Jansen',
            'email' => 'jan@example.org',
            'phone' => '+31612345678',
            'pickup' => 'Amsterdam',
            'dropoff' => 'Schiphol Airport',
            'pickup_date' => now()->addDay()->toDateString(),
            'pickup_time' => '09:00',
            'consent' => true,
            'locale' => 'nl',
        ])->assertCreated();

        Http::assertSent(fn ($request) => str_contains($request['subject'], 'Nieuwe transferaanvraag'));
        Http::assertSent(fn ($request) => str_contains($request['subject'], 'Wij hebben uw transferaanvraag ontvangen')
            && str_contains($request['html'], 'Bedankt, Jan Jansen.'));
    }
}
