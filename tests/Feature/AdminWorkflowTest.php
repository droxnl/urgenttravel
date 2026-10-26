<?php

namespace Tests\Feature;

use App\Models\Page;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_an_admin_can_sign_in(): void
    {
        $this->postJson('/api/admin/login', [
            'email' => 'admin@urgent-travelservices.nl',
            'password' => 'ChangeMe123!',
        ])->assertOk()
            ->assertJsonPath('user.email', 'admin@urgent-travelservices.nl');

        $this->assertAuthenticated();
    }

    public function test_an_admin_can_update_page_copy_and_seo(): void
    {
        $user = User::query()->firstOrFail();
        $page = Page::query()->where('slug', 'taxi-almere-naar-schiphol')->firstOrFail();

        $payload = [
            ...$page->only([
                'title', 'slug', 'path', 'template', 'hero_eyebrow', 'hero_title',
                'hero_subtitle', 'hero_image', 'excerpt', 'seo_title',
                'seo_description', 'focus_keyword', 'canonical_url', 'robots',
                'og_title', 'og_description', 'og_image', 'sort_order', 'is_published',
            ]),
            'content_html' => '<h2>Updated transfer</h2><p>Clear airport travel to privévervoer.</p><script>alert(1)</script>',
            'schema_json' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
            'images' => [],
        ];

        $response = $this->actingAs($user)
            ->putJson("/api/admin/pages/{$page->id}", $payload)
            ->assertOk();

        $this->assertStringNotContainsString('<script>', $response->json('data.content_html'));

        $this->assertDatabaseHas('pages', [
            'id' => $page->id,
            'content_html' => '<div id="uts-content"><h2>Updated transfer</h2><p>Clear airport travel to privévervoer.</p></div>',
        ]);
    }

    public function test_openai_settings_never_return_the_secret(): void
    {
        $this->actingAs(User::query()->firstOrFail())
            ->putJson('/api/admin/settings', [
                'openai_enabled' => false,
                'openai_api_key' => 'sk-test-secret',
            ])->assertOk();

        $response = $this->getJson('/api/admin/settings')->assertOk();

        $this->assertStringNotContainsString('sk-test-secret', $response->getContent());
    }

    public function test_hostinger_mail_token_is_encrypted_and_never_returned(): void
    {
        $admin = User::query()->firstOrFail();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'mail_enabled' => false,
                'hostinger_mail_api_token' => 'hostinger-private-token',
                'hostinger_mailbox_resource_id' => 'AC123',
                'mail_sender_email' => 'info@urgent-travelservices.nl',
            ])->assertOk();

        $response = $this->getJson('/api/admin/settings')->assertOk();

        $this->assertStringNotContainsString('hostinger-private-token', $response->getContent());
        $this->assertSame('••••••••', $response->json('settings.hostinger_mail_api_token'));
        $this->assertDatabaseMissing('site_settings', [
            'key' => 'hostinger_mail_api_token',
            'value' => 'hostinger-private-token',
        ]);
    }

    public function test_email_notification_checkboxes_persist_after_saving_and_reloading(): void
    {
        $admin = User::query()->firstOrFail();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'mail_enabled' => true,
                'mail_send_admin' => true,
                'mail_send_customer' => true,
            ])
            ->assertOk()
            ->assertJsonPath('settings.mail_enabled', '1')
            ->assertJsonPath('settings.mail_send_admin', '1')
            ->assertJsonPath('settings.mail_send_customer', '1');

        $this->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('settings.mail_enabled', '1')
            ->assertJsonPath('settings.mail_send_admin', '1')
            ->assertJsonPath('settings.mail_send_customer', '1');

        $this->assertDatabaseHas('site_settings', ['key' => 'mail_enabled', 'value' => '1']);
        $this->assertDatabaseHas('site_settings', ['key' => 'mail_send_admin', 'value' => '1']);
        $this->assertDatabaseHas('site_settings', ['key' => 'mail_send_customer', 'value' => '1']);
    }

    public function test_an_admin_can_update_the_dutch_page_translation(): void
    {
        $admin = User::query()->firstOrFail();
        $page = Page::query()->where('slug', 'services/airport-transfers')->firstOrFail();
        $translation = $page->translation('nl');

        $this->actingAs($admin)
            ->putJson("/api/admin/pages/{$page->id}/translations/nl", [
                'path' => $translation->path,
                'title' => 'Bijgewerkte luchthaventransfers',
                'hero_eyebrow' => 'Luchthavenvervoer',
                'hero_title' => 'Een verzorgde transfer naar Schiphol',
                'hero_subtitle' => 'Persoonlijk vervoer van deur tot deur.',
                'excerpt' => 'Bijgewerkte Nederlandse samenvatting.',
                'content_html' => '<h2>Nederlandse inhoud</h2><p>Volledig vertaald.</p>',
                'seo_title' => 'Luchthaventransfers Schiphol',
                'seo_description' => 'Betrouwbaar luchthavenvervoer met een privéchauffeur.',
                'canonical_url' => $translation->canonical_url,
                'og_title' => 'Luchthaventransfers Schiphol',
                'og_description' => 'Betrouwbaar luchthavenvervoer met een privéchauffeur.',
                'schema_json' => ['@context' => 'https://schema.org', '@type' => 'WebPage'],
            ])
            ->assertOk()
            ->assertJsonPath('data.translations.0.title', 'Bijgewerkte luchthaventransfers');

        $this->assertDatabaseHas('page_translations', [
            'page_id' => $page->id,
            'locale' => 'nl',
            'title' => 'Bijgewerkte luchthaventransfers',
        ]);
    }

    public function test_an_admin_can_edit_structured_page_blocks(): void
    {
        $admin = User::query()->firstOrFail();
        $page = Page::query()->where('slug', 'home')->firstOrFail();

        $this->actingAs($admin)
            ->putJson("/api/admin/pages/{$page->id}/blocks/en", [
                'blocks' => [[
                    'type' => 'service_grid',
                    'sort_order' => 10,
                    'is_active' => true,
                    'content' => [
                        'eyebrow' => 'Test eyebrow',
                        'heading_line_1' => 'Test heading',
                        'heading_line_2' => 'Second line',
                        'intro' => 'Editable structured introduction.',
                        'link_label' => 'View service',
                        'items' => [[
                            'title' => 'Editable airport transfer',
                            'kicker' => 'Custom kicker',
                            'description' => 'Custom card description.',
                            'link' => '/services/airport-transfers/',
                            'icon' => 'plane',
                            'image' => '',
                        ]],
                    ],
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('data.blocks.0.content.items.0.title', 'Editable airport transfer');

        $this->assertDatabaseHas('page_blocks', [
            'page_id' => $page->id,
            'locale' => 'en',
            'type' => 'service_grid',
        ]);
    }
}
