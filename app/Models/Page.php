<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'wordpress_id',
        'slug',
        'path',
        'title',
        'template',
        'hero_eyebrow',
        'hero_title',
        'hero_subtitle',
        'hero_image',
        'excerpt',
        'content_html',
        'seo_title',
        'seo_description',
        'focus_keyword',
        'canonical_url',
        'robots',
        'og_title',
        'og_description',
        'og_image',
        'schema_json',
        'images',
        'is_published',
        'sort_order',
        'published_at',
        'modified_at',
    ];

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'images' => 'array',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'modified_at' => 'datetime',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function translations(): HasMany
    {
        return $this->hasMany(PageTranslation::class);
    }

    public function blocks(): HasMany
    {
        return $this->hasMany(PageBlock::class)->orderBy('sort_order');
    }

    public function translation(string $locale = 'en'): ?PageTranslation
    {
        if ($locale === 'en') {
            return null;
        }

        return $this->relationLoaded('translations')
            ? $this->translations->firstWhere('locale', $locale)
            : $this->translations()->where('locale', $locale)->first();
    }
}
