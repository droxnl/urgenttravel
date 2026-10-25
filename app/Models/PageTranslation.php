<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PageTranslation extends Model
{
    protected $fillable = [
        'page_id',
        'locale',
        'path',
        'title',
        'hero_eyebrow',
        'hero_title',
        'hero_subtitle',
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
        'modified_at',
    ];

    protected function casts(): array
    {
        return [
            'schema_json' => 'array',
            'modified_at' => 'datetime',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }
}
