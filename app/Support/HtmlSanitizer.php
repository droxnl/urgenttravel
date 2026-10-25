<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

class HtmlSanitizer
{
    private const ALLOWED_TAGS = [
        'a', 'blockquote', 'br', 'em', 'figcaption', 'figure', 'h2', 'h3', 'h4',
        'h5', 'h6', 'hr', 'img', 'li', 'ol', 'p', 'span', 'strong', 'table',
        'tbody', 'td', 'th', 'thead', 'tr', 'ul',
    ];

    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'target', 'rel', 'title'],
        'img' => ['src', 'alt', 'title', 'loading'],
        '*' => ['class'],
    ];

    public static function clean(?string $html): string
    {
        if (! $html) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML(
            '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="uts-content">'.$html.'</div></body></html>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();

        $root = $document->getElementById('uts-content');
        if (! $root) {
            return '';
        }

        foreach (iterator_to_array($root->getElementsByTagName('*')) as $element) {
            if (in_array(strtolower($element->tagName), ['script', 'style', 'iframe', 'object', 'embed', 'form'], true)) {
                $element->parentNode?->removeChild($element);
            }
        }

        self::sanitizeChildren($root);

        return $root->ownerDocument->saveHTML($root) ?: '';
    }

    private static function sanitizeChildren(DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMElement) {
                $tag = strtolower($child->tagName);
                if (! in_array($tag, self::ALLOWED_TAGS, true)) {
                    self::unwrap($child);

                    continue;
                }

                foreach (iterator_to_array($child->attributes) as $attribute) {
                    $name = strtolower($attribute->name);
                    $allowed = array_merge(self::ALLOWED_ATTRIBUTES['*'], self::ALLOWED_ATTRIBUTES[$tag] ?? []);
                    if (! in_array($name, $allowed, true)) {
                        $child->removeAttribute($attribute->name);
                    }
                }

                if ($tag === 'a') {
                    $href = $child->getAttribute('href');
                    if ($href && ! preg_match('/^(https?:\/\/|\/|mailto:|tel:|#)/i', $href)) {
                        $child->removeAttribute('href');
                    }
                    $child->setAttribute('rel', 'noopener');
                }

                if ($tag === 'img') {
                    $src = $child->getAttribute('src');
                    if (! preg_match('/^(\/images\/|https?:\/\/)/i', $src)) {
                        $child->removeAttribute('src');
                    }
                }
            }

            if ($child->hasChildNodes()) {
                self::sanitizeChildren($child);
            }
        }
    }

    private static function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }
}
