<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageTranslation;
use Illuminate\Database\Seeder;
use RuntimeException;

class PageTranslationSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/wordpress-pages-nl.json');
        if (! file_exists($path)) {
            throw new RuntimeException(
                'The Dutch content file is missing. Run: python scripts/translate-pages-nl.py'
            );
        }

        $payload = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($payload['pages'] as $translation) {
            $page = Page::query()->where('wordpress_id', $translation['wordpress_id'])->first();
            if (! $page) {
                continue;
            }

            PageTranslation::query()->updateOrCreate(
                [
                    'page_id' => $page->id,
                    'locale' => $translation['locale'],
                ],
                [
                    'path' => $translation['path'],
                    'title' => $translation['title'],
                    'hero_eyebrow' => $translation['hero_eyebrow'],
                    'hero_title' => $translation['hero_title'],
                    'hero_subtitle' => $translation['hero_subtitle'],
                    'excerpt' => $translation['excerpt'],
                    'content_html' => $translation['content_html'],
                    'seo_title' => $translation['seo_title'],
                    'seo_description' => $translation['seo_description'],
                    'focus_keyword' => $translation['focus_keyword'],
                    'canonical_url' => $translation['canonical_url'],
                    'robots' => $translation['robots'],
                    'og_title' => $translation['og_title'],
                    'og_description' => $translation['og_description'],
                    'og_image' => $translation['og_image'],
                    'schema_json' => $translation['schema_json'],
                    'modified_at' => $translation['modified_at']
                        ? date('Y-m-d H:i:s', strtotime($translation['modified_at']))
                        : null,
                ]
            );
        }
    }
}
