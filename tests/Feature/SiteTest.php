<?php

namespace Tests\Feature;

use App\Models\PageTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed();
    }

    public function test_homepage_renders_with_preserved_seo(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('VIP Chauffeur Services Amsterdam', false)
            ->assertSee('https://urgent-travelservices.nl/', false)
            ->assertSee('/images/brand/urgent-travel-services-logo.png', false)
            ->assertSee('window.__APP_BOOTSTRAP__', false);

        $this->assertFileExists(public_path('images/brand/urgent-travel-services-logo.png'));
        $this->assertFileExists(public_path('images/flags/gb.svg'), 'The British flag used by the language switcher is missing.');
        $this->assertFileExists(public_path('images/flags/nl.svg'), 'The Dutch flag used by the language switcher is missing.');
    }

    public function test_all_imported_pages_are_available_from_the_api(): void
    {
        $this->getJson('/api/pages')
            ->assertOk()
            ->assertJsonCount(39, 'data');

        $this->getJson('/api/page?path=/taxi-almere-naar-schiphol/')
            ->assertOk()
            ->assertJsonPath('data.path', '/taxi-almere-naar-schiphol/')
            ->assertJsonPath('data.template', 'landing')
            ->assertJsonPath('data.hero_title', 'Taxi Almere naar Schiphol');
    }

    public function test_dutch_pages_render_with_dutch_metadata(): void
    {
        $response = $this->get('/nl/');

        $response->assertOk()
            ->assertSee('<html lang="nl">', false)
            ->assertSee('VIP-chauffeursservice Amsterdam', false)
            ->assertSee('hreflang="nl"', false);

        $this->getJson('/api/page?path=/nl/services/airport-transfers/')
            ->assertOk()
            ->assertJsonPath('data.locale', 'nl')
            ->assertJsonPath('data.path', '/nl/services/airport-transfers/')
            ->assertJsonPath('data.title', 'Professionele Luchthaventransferdiensten')
            ->assertJsonPath('data.alternates.en', '/services/airport-transfers/');
    }

    public function test_homepage_structured_sections_are_not_dumped_into_the_article(): void
    {
        $response = $this->getJson('/api/page?path=/')->assertOk();

        $response->assertJsonCount(4, 'data.blocks')
            ->assertJsonPath('data.blocks.1.type', 'service_grid')
            ->assertJsonCount(6, 'data.blocks.1.content.items');

        $this->assertStringNotContainsString('Our Transportation Services', $response->json('data.content_html'));
        $this->assertStringNotContainsString('WORKINGAREA', $response->json('data.content_html'));
    }

    public function test_all_dutch_page_content_is_valid_utf8(): void
    {
        foreach (PageTranslation::query()->where('locale', 'nl')->get() as $translation) {
            $this->assertStringNotContainsString('Ã', $translation->content_html ?? '', $translation->path);
            $this->assertStringNotContainsString('Â', $translation->content_html ?? '', $translation->path);
            $this->assertStringNotContainsString('â€', $translation->content_html ?? '', $translation->path);
        }
    }

    public function test_page_sitemap_contains_all_published_pages(): void
    {
        $response = $this->get('/page-sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('taxi-almere-naar-schiphol', false);

        $this->assertSame(78, substr_count($response->getContent(), '<url>'));
        $response->assertSee('hreflang="nl"', false);
    }
}
