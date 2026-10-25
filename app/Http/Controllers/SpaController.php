<?php

namespace App\Http\Controllers;

use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Support\Locale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class SpaController extends Controller
{
    public function __invoke(Request $request, ?string $path = null): View
    {
        return $this->renderPage($request, $path);
    }

    public function admin(Request $request): View
    {
        return view('app', [
            'bootstrap' => [
                'mode' => 'admin',
                'settings' => [
                    ...PageResource::publicSettings(),
                    'openai_configured' => filled(SiteSetting::get('openai_api_key') ?: config('services.openai.key')),
                ],
                'user' => $request->user()?->only(['id', 'name', 'email']),
            ],
            'seo' => [
                'title' => 'Content manager | Urgent Travel Services',
                'description' => 'Manage website pages, SEO, OpenAI copy settings and booking inquiries.',
                'robots' => 'noindex, nofollow',
            ],
            'locale' => 'en',
        ]);
    }

    private function renderPage(Request $request, ?string $path = null): View
    {
        [$locale, $normalizedPath] = Locale::fromPath($path ?: $request->path());

        $page = Page::query()
            ->with(['translations', 'blocks'])
            ->where('is_published', true)
            ->where(fn ($query) => $query->where('path', $normalizedPath)->orWhere('slug', trim($normalizedPath, '/')))
            ->firstOrFail();

        $pageData = (new PageResource($page, $locale))->resolve();
        $canonical = $pageData['canonical_url'] ?: url($pageData['path']);
        $seo = [
            'title' => $pageData['seo_title'] ?: $pageData['title'],
            'description' => $pageData['seo_description'] ?: $pageData['excerpt'],
            'canonical' => $canonical,
            'robots' => $pageData['robots'],
            'og_title' => $pageData['og_title'] ?: $pageData['seo_title'] ?: $pageData['title'],
            'og_description' => $pageData['og_description'] ?: $pageData['seo_description'],
            'og_image' => $pageData['og_image'] ?: $pageData['hero_image'],
            'schema' => $pageData['schema_json'],
            'alternates' => $pageData['alternates'],
            'locale' => $locale === 'nl' ? 'nl_NL' : 'en_US',
        ];

        return view('app', [
            'bootstrap' => [
                'mode' => 'site',
                'page' => $pageData,
                'settings' => PageResource::publicSettings(),
            ],
            'seo' => $seo,
            'locale' => $locale,
        ]);
    }
}
