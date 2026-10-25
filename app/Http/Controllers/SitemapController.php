<?php

namespace App\Http\Controllers;

use App\Models\Page;
use App\Support\Locale;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function pages(): Response
    {
        $pages = Page::query()->with('translations')->where('is_published', true)->orderBy('sort_order')->get();
        $xml = [
            '<?xml version="1.0" encoding="UTF-8"?>',
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:xhtml="http://www.w3.org/1999/xhtml">',
        ];

        foreach ($pages as $page) {
            $image = $page->og_image ?: $page->hero_image;
            $translation = $page->translation('nl');
            $alternates = [
                'en' => $page->path,
                'nl' => $translation?->path,
            ];

            foreach ([
                ['path' => $page->path, 'modified' => $page->modified_at ?: $page->updated_at, 'translate' => false],
                ['path' => $translation?->path, 'modified' => $translation?->modified_at ?: $translation?->updated_at, 'translate' => true],
            ] as $alternate) {
                if (! $alternate['path'] || ! $alternate['modified']) {
                    continue;
                }

                $xml[] = '  <url>';
                $pageUrl = rtrim(url('/'), '/').Locale::path(
                    $page->path,
                    $alternate['translate'] ? 'nl' : 'en'
                );
                $xml[] = '    <loc>'.htmlspecialchars($pageUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</loc>';
                if ($alternates['en']) {
                    $xml[] = '    <xhtml:link rel="alternate" hreflang="en" href="'.htmlspecialchars(rtrim(url('/'), '/').$alternates['en'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'"/>';
                }
                if ($alternates['nl']) {
                    $xml[] = '    <xhtml:link rel="alternate" hreflang="nl" href="'.htmlspecialchars(rtrim(url('/'), '/').$alternates['nl'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'"/>';
                    $xml[] = '    <xhtml:link rel="alternate" hreflang="x-default" href="'.htmlspecialchars(rtrim(url('/'), '/').$alternates['en'], ENT_XML1 | ENT_QUOTES, 'UTF-8').'"/>';
                }
                $xml[] = '    <lastmod>'.$alternate['modified']->toAtomString().'</lastmod>';
                if ($image && ! $alternate['translate']) {
                    $xml[] = '    <image:image>';
                    $xml[] = '      <image:loc>'.htmlspecialchars(url($image), ENT_XML1 | ENT_QUOTES, 'UTF-8').'</image:loc>';
                    $xml[] = '    </image:image>';
                }
                $xml[] = '  </url>';
            }
        }

        $xml[] = '</urlset>';

        return response(implode("\n", $xml), 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'
            .'<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'
            .'<sitemap><loc>'.e(url('/page-sitemap.xml')).'</loc></sitemap>'
            .'</sitemapindex>';

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    public function robots(): Response
    {
        return response(
            "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /api/admin\n\nSitemap: ".url('/sitemap.xml')."\n",
            200,
            ['Content-Type' => 'text/plain; charset=UTF-8']
        );
    }
}
