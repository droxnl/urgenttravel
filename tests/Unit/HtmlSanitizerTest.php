<?php

namespace Tests\Unit;

use App\Support\HtmlSanitizer;
use PHPUnit\Framework\TestCase;

class HtmlSanitizerTest extends TestCase
{
    public function test_utf8_dutch_text_survives_sanitization(): void
    {
        $clean = HtmlSanitizer::clean(
            '<p>Privévervoer voor zakelijke reizigers — één vlekkeloze reis.</p>'
        );

        $this->assertStringContainsString('Privévervoer', $clean);
        $this->assertStringNotContainsString('Ã', $clean);
        $this->assertStringNotContainsString('Â', $clean);
        $this->assertStringNotContainsString('â€', $clean);
    }
}
