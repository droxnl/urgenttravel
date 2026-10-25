<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Support\Locale;
use Illuminate\Http\Request;

class PublicPageController extends Controller
{
    public function index()
    {
        $locale = request()->query('locale', 'en') === 'nl' ? 'nl' : 'en';
        $pages = Page::query()
            ->with(['translations', 'blocks'])
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Page $page) => (new PageResource($page, $locale))->resolve());

        return response()->json(['data' => $pages]);
    }

    public function show(Request $request)
    {
        [$locale, $path] = Locale::fromPath((string) $request->query('path', '/'));

        return new PageResource(
            Page::query()
                ->with(['translations', 'blocks'])
                ->where('is_published', true)
                ->where(fn ($query) => $query->where('path', $path)->orWhere('slug', trim($path, '/')))
                ->firstOrFail(),
            $locale
        );
    }
}
