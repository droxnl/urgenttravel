<?php

namespace App\Services;

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class OpenAiService
{
    public function isConfigured(): bool
    {
        return $this->enabled() && filled($this->apiKey());
    }

    public function enabled(): bool
    {
        return filter_var(
            SiteSetting::get('openai_enabled', config('services.openai.enabled', false)),
            FILTER_VALIDATE_BOOL
        );
    }

    public function apiKey(): ?string
    {
        return SiteSetting::get('openai_api_key') ?: config('services.openai.key');
    }

    public function testConnection(): array
    {
        $response = $this->client()->get('models');
        $response->throw();

        return [
            'ok' => true,
            'model' => $this->model(),
            'models_available' => count($response->json('data', [])),
        ];
    }

    public function generateForPage(Page $page, string $action, ?string $instruction = null): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('OpenAI is not configured. Add an API key and enable it in Settings.');
        }

        $instruction = $instruction ?: match ($action) {
            'seo' => 'Write conversion-focused SEO metadata for this chauffeur and airport transfer page.',
            'hero' => 'Rewrite the hero heading and supporting sentence with a premium, direct tone.',
            'body' => 'Improve the page introduction while retaining every factual detail and service promise.',
            default => 'Improve the supplied page copy.',
        };

        $payload = [
            'action' => $action,
            'instruction' => $instruction,
            'language' => SiteSetting::get('openai_language', 'en'),
            'tone' => SiteSetting::get('openai_tone', 'premium, calm, dependable, concise'),
            'response_schema' => match ($action) {
                'seo' => [
                    'seo_title' => 'SEO title, ideally under 60 characters',
                    'seo_description' => 'Meta description, ideally under 160 characters',
                    'focus_keyword' => 'One relevant focus keyword',
                ],
                'hero' => [
                    'hero_eyebrow' => 'Short uppercase-friendly label',
                    'hero_title' => 'Hero heading, plain text',
                    'hero_subtitle' => 'One or two supporting sentences',
                ],
                default => [
                    'content_html' => 'Safe semantic HTML using only p, h2, h3, ul, li, strong and em',
                ],
            },
            'page' => [
                'title' => $page->title,
                'slug' => $page->slug,
                'hero_title' => $page->hero_title,
                'hero_subtitle' => $page->hero_subtitle,
                'seo_title' => $page->seo_title,
                'seo_description' => $page->seo_description,
                'excerpt' => $page->excerpt,
                'body' => Str::limit(strip_tags($page->content_html ?? ''), 8000),
            ],
        ];

        $system = SiteSetting::get(
            'openai_system_prompt',
            'You are the senior copywriter for Urgent Travel Services, a 24/7 VIP chauffeur and Schiphol airport transfer company in the Netherlands. Preserve factual claims. Never invent prices, licenses, reviews or guarantees. Write polished, specific, conversion-ready copy. Return only a JSON object matching the response_schema supplied in the user payload.'
        );

        $response = $this->client()->post('chat/completions', [
            'model' => $this->model(),
            'temperature' => 0.45,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                [
                    'role' => 'user',
                    'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
        ]);
        $response->throw();

        $content = $response->json('choices.0.message.content');
        $decoded = json_decode((string) $content, true);
        if (! is_array($decoded)) {
            throw new RuntimeException('The AI response was not valid JSON.');
        }

        return [
            ...$decoded,
            'model' => $response->json('model', $this->model()),
            'usage' => $response->json('usage'),
        ];
    }

    private function client(): PendingRequest
    {
        return Http::withToken((string) $this->apiKey())
            ->acceptJson()
            ->asJson()
            ->timeout(75)
            ->baseUrl(rtrim($this->baseUrl(), '/').'/v1/');
    }

    private function baseUrl(): string
    {
        return SiteSetting::get('openai_base_url', config('services.openai.base_url', 'https://api.openai.com'));
    }

    private function model(): string
    {
        return SiteSetting::get('openai_model', config('services.openai.model', 'gpt-4o-mini'));
    }
}
