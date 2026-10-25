<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;
use RuntimeException;

class WordPressContentSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/wordpress-pages.json');
        if (! file_exists($path)) {
            throw new RuntimeException(
                'The imported content file is missing. Run: python scripts/import-wordpress.py'
            );
        }

        $payload = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        foreach ($payload['pages'] as $page) {
            Page::query()->updateOrCreate(
                ['wordpress_id' => $page['wordpress_id']],
                [
                    ...$page,
                    'published_at' => $page['published_at'] ? date('Y-m-d H:i:s', strtotime($page['published_at'])) : null,
                    'modified_at' => $page['modified_at'] ? date('Y-m-d H:i:s', strtotime($page['modified_at'])) : null,
                ]
            );
        }
    }
}
