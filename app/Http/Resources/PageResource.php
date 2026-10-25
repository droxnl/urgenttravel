<?php

namespace App\Http\Resources;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PageResource extends JsonResource
{
    public function __construct($resource, private readonly string $locale = 'en')
    {
        parent::__construct($resource);
    }

    public function toArray(Request $request): array
    {
        $translation = $this->resource->translation($this->locale);
        $path = $translation?->path ?: $this->resource->path;
        $isAdminRequest = str_starts_with($request->path(), 'api/admin');
        $blocks = $this->resource->relationLoaded('blocks')
            ? $this->resource->blocks
                ->where('locale', $this->locale)
                ->when(! $isAdminRequest, fn ($items) => $items->where('is_active', true))
                ->values()
            : collect();

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'locale' => $this->locale,
            'path' => $path,
            'alternates' => [
                'en' => $this->resource->path,
                'nl' => $this->resource->translation('nl')?->path,
            ],
            'title' => $translation?->title ?: $this->title,
            'template' => $this->template,
            'hero_eyebrow' => $translation?->hero_eyebrow ?: $this->hero_eyebrow,
            'hero_title' => $translation?->hero_title ?: $this->hero_title,
            'hero_subtitle' => $translation?->hero_subtitle ?: $this->hero_subtitle,
            'hero_image' => $this->hero_image,
            'excerpt' => $translation?->excerpt ?: $this->excerpt,
            'content_html' => $translation?->content_html ?: $this->content_html,
            'seo_title' => $translation?->seo_title ?: $this->seo_title,
            'seo_description' => $translation?->seo_description ?: $this->seo_description,
            'focus_keyword' => $translation?->focus_keyword ?: $this->focus_keyword,
            'canonical_url' => $translation?->canonical_url ?: $this->canonical_url,
            'robots' => $translation?->robots ?: $this->robots,
            'og_title' => $translation?->og_title ?: $this->og_title,
            'og_description' => $translation?->og_description ?: $this->og_description,
            'og_image' => $translation?->og_image ?: $this->og_image,
            'schema_json' => $translation?->schema_json ?: $this->schema_json,
            'images' => $this->images ?? [],
            'is_published' => $this->is_published,
            'sort_order' => $this->sort_order,
            'published_at' => $this->published_at?->toIso8601String(),
            'modified_at' => $this->modified_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'blocks' => $this->when(
                $this->resource->relationLoaded('blocks'),
                fn () => $blocks->map(fn ($block) => [
                    'id' => $block->id,
                    'locale' => $block->locale,
                    'type' => $block->type,
                    'content' => $block->content,
                    'sort_order' => $block->sort_order,
                    'is_active' => $block->is_active,
                ])->values(),
            ),
            'translations' => $this->when(
                $isAdminRequest && $this->resource->relationLoaded('translations'),
                fn () => $this->resource->translations->map(fn ($translation) => [
                    'id' => $translation->id,
                    'locale' => $translation->locale,
                    'path' => $translation->path,
                    'title' => $translation->title,
                    'hero_eyebrow' => $translation->hero_eyebrow,
                    'hero_title' => $translation->hero_title,
                    'hero_subtitle' => $translation->hero_subtitle,
                    'excerpt' => $translation->excerpt,
                    'content_html' => $translation->content_html,
                    'seo_title' => $translation->seo_title,
                    'seo_description' => $translation->seo_description,
                    'canonical_url' => $translation->canonical_url,
                    'og_title' => $translation->og_title,
                    'og_description' => $translation->og_description,
                    'schema_json' => $translation->schema_json,
                    'modified_at' => $translation->modified_at?->toIso8601String(),
                    'blocks' => $this->resource->relationLoaded('blocks')
                        ? $this->resource->blocks
                            ->where('locale', $translation->locale)
                            ->values()
                            ->map(fn ($block) => [
                                'id' => $block->id,
                                'locale' => $block->locale,
                                'type' => $block->type,
                                'content' => $block->content,
                                'sort_order' => $block->sort_order,
                                'is_active' => $block->is_active,
                            ])
                        : [],
                ])->values(),
            ),
        ];
    }

    public static function publicSettings(): array
    {
        $settings = SiteSetting::allValues();

        return array_intersect_key($settings, array_flip([
            'site_name',
            'site_tagline',
            'phone',
            'whatsapp',
            'email',
            'address',
            'city',
            'postcode',
            'country',
            'kvk',
            'vat',
            'booking_url',
        ]));
    }
}
