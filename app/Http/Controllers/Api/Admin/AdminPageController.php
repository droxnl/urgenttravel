<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PageResource;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Services\OpenAiService;
use App\Support\HtmlSanitizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class AdminPageController extends Controller
{
    public function index()
    {
        return PageResource::collection(
            Page::query()->orderBy('sort_order')->orderBy('title')->get()
        );
    }

    public function show(Page $page): PageResource
    {
        $page->load(['translations', 'blocks']);

        return new PageResource($page);
    }

    public function updateBlocks(Request $request, Page $page, string $locale): PageResource
    {
        abort_unless(in_array($locale, ['en', 'nl'], true), 404);
        $validated = $request->validate([
            'blocks' => ['present', 'array'],
            'blocks.*.type' => ['required', 'in:trust_strip,service_grid,fleet_grid,coverage_grid,related_links'],
            'blocks.*.sort_order' => ['required', 'integer', 'min:0'],
            'blocks.*.is_active' => ['required', 'boolean'],
            'blocks.*.content' => ['required', 'array'],
        ]);

        $page->blocks()->where('locale', $locale)->delete();
        foreach ($validated['blocks'] as $block) {
            $page->blocks()->create([
                'locale' => $locale,
                'type' => $block['type'],
                'content' => $block['content'],
                'sort_order' => $block['sort_order'],
                'is_active' => $block['is_active'],
            ]);
        }

        $page->load(['translations', 'blocks']);

        return new PageResource($page);
    }

    public function updateTranslation(Request $request, Page $page, string $locale): PageResource
    {
        abort_unless($locale === 'nl', 404);
        $translation = $page->translations()->where('locale', $locale)->firstOrFail();

        $validated = $request->validate([
            'path' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:190'],
            'hero_eyebrow' => ['nullable', 'string', 'max:190'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:2000'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content_html' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'schema_json' => ['nullable'],
        ]);
        $validated['path'] = '/nl/'.ltrim(preg_replace('/^\/nl\/?/', '', $validated['path']), '/');
        $validated['path'] = $validated['path'] === '/nl/' ? '/nl/' : rtrim($validated['path'], '/').'/';
        $validated['content_html'] = HtmlSanitizer::clean($validated['content_html'] ?? null);
        $validated['schema_json'] = $this->decodeSchema($validated['schema_json'] ?? null);
        $validated['modified_at'] = now();
        $translation->update($validated);
        $page->load(['translations', 'blocks']);

        return new PageResource($page);
    }

    public function update(Request $request, Page $page, OpenAiService $openAi): PageResource
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:190'],
            'slug' => ['required', 'string', 'max:190', Rule::unique('pages', 'slug')->ignore($page)],
            'path' => ['required', 'string', 'max:255', Rule::unique('pages', 'path')->ignore($page)],
            'template' => ['required', 'in:home,services,service,landing,general,booking,contact,rates,fleet'],
            'hero_eyebrow' => ['nullable', 'string', 'max:190'],
            'hero_title' => ['required', 'string', 'max:255'],
            'hero_subtitle' => ['nullable', 'string', 'max:2000'],
            'hero_image' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'content_html' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'focus_keyword' => ['nullable', 'string', 'max:190'],
            'canonical_url' => ['nullable', 'url', 'max:255'],
            'robots' => ['required', 'string', 'max:100'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:255'],
            'schema_json' => ['nullable'],
            'images' => ['nullable', 'array'],
            'is_published' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'generate_ai_seo' => ['sometimes', 'boolean'],
        ]);

        $validated['path'] = '/'.ltrim($validated['path'], '/');
        $validated['slug'] = trim($validated['slug'], '/');
        $validated['content_html'] = HtmlSanitizer::clean($validated['content_html'] ?? null);
        $validated['schema_json'] = $this->decodeSchema($validated['schema_json'] ?? null);
        $validated['modified_at'] = now();
        unset($validated['generate_ai_seo']);

        $page->update($validated);

        $shouldGenerate = $request->boolean('generate_ai_seo')
            && filter_var(SiteSetting::get('openai_auto_seo', false), FILTER_VALIDATE_BOOL);

        if ($shouldGenerate && $openAi->isConfigured()) {
            try {
                $generated = $openAi->generateForPage($page, 'seo');
                $page->update([
                    'seo_title' => $generated['seo_title'] ?? $page->seo_title,
                    'seo_description' => $generated['seo_description'] ?? $page->seo_description,
                    'focus_keyword' => $generated['focus_keyword'] ?? $page->focus_keyword,
                    'og_title' => $generated['seo_title'] ?? $page->og_title,
                    'og_description' => $generated['seo_description'] ?? $page->og_description,
                    'modified_at' => now(),
                ]);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return new PageResource($page->fresh());
    }

    public function generate(Request $request, Page $page, OpenAiService $openAi): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:seo,hero,body'],
            'instruction' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            return response()->json([
                'data' => $openAi->generateForPage($page, $validated['action'], $validated['instruction'] ?? null),
            ]);
        } catch (Throwable $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    private function decodeSchema(mixed $schema): ?array
    {
        if (is_string($schema) && trim($schema) !== '') {
            $decoded = json_decode($schema, true);
            if (! is_array($decoded)) {
                throw ValidationException::withMessages([
                    'schema_json' => 'The schema field must contain valid JSON.',
                ]);
            }

            return $decoded;
        }

        return is_array($schema) ? $schema : null;
    }
}
