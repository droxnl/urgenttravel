<?php

namespace Database\Seeders;

use App\Models\Page;
use App\Models\PageBlock;
use DOMDocument;
use DOMElement;
use DOMNode;
use Illuminate\Database\Seeder;

class PageBlockSeeder extends Seeder
{
    public function run(): void
    {
        $serviceItemsEn = [
            ['title' => 'Airport transfers', 'kicker' => 'Schiphol, Rotterdam & Eindhoven', 'description' => 'Flight-aware, door-to-door transfers with a professional chauffeur and a calm, punctual airport experience.', 'link' => '/services/airport-transfers/', 'icon' => 'plane', 'image' => ''],
            ['title' => 'VIP chauffeur', 'kicker' => 'Discreet by design', 'description' => 'A polished executive journey for international visitors, special occasions and VIP itineraries.', 'link' => '/services/vip-chauffeur-service/', 'icon' => 'crown', 'image' => ''],
            ['title' => 'Business travel', 'kicker' => 'Move with purpose', 'description' => 'Dependable corporate ground transport for meetings, roadshows, offices and visiting colleagues.', 'link' => '/services/business-transportation/', 'icon' => 'briefcase', 'image' => ''],
            ['title' => 'Private chauffeur', 'kicker' => 'Your day, handled', 'description' => 'Flexible private chauffeur hire for appointments, family plans, dinners and full-day itineraries.', 'link' => '/services/private-chauffeur-service/', 'icon' => 'car', 'image' => ''],
            ['title' => 'City to city', 'kicker' => 'Beyond Amsterdam', 'description' => 'Comfortable point-to-point chauffeur travel between cities, hotels and airports across the Netherlands.', 'link' => '/services/city-to-city-transportation/', 'icon' => 'route', 'image' => ''],
            ['title' => 'Private sightseeing', 'kicker' => 'The Netherlands, your way', 'description' => 'Personalized sightseeing by private car, with the freedom to shape each stop around your schedule.', 'link' => '/services/sightseeing-tours/', 'icon' => 'compass', 'image' => ''],
        ];

        $serviceItemsNl = [
            ['title' => 'Luchthaventransfers', 'kicker' => 'Schiphol, Rotterdam & Eindhoven', 'description' => 'Luchthaventransfers met vluchtmonitoring en een professionele chauffeur, van deur tot deur.', 'link' => '/services/airport-transfers/', 'icon' => 'plane', 'image' => ''],
            ['title' => 'VIP-chauffeur', 'kicker' => 'Discreet ontworpen', 'description' => 'Een verzorgde directiereis voor internationale gasten, speciale gelegenheden en VIP-programma’s.', 'link' => '/services/vip-chauffeur-service/', 'icon' => 'crown', 'image' => ''],
            ['title' => 'Zakelijk vervoer', 'kicker' => 'Met doel voor ogen', 'description' => 'Betrouwbaar zakelijk vervoer naar vergaderingen, presentaties, kantoren en bezoekende collega’s.', 'link' => '/services/business-transportation/', 'icon' => 'briefcase', 'image' => ''],
            ['title' => 'Privéchauffeur', 'kicker' => 'Uw dag, geregeld', 'description' => 'Flexibele privéchauffeur voor afspraken, familieplannen, diners en volledige dagprogramma’s.', 'link' => '/services/private-chauffeur-service/', 'icon' => 'car', 'image' => ''],
            ['title' => 'Stad naar stad', 'kicker' => 'Verder dan Amsterdam', 'description' => 'Comfortabel chauffeursvervoer tussen steden, hotels en luchthavens in heel Nederland.', 'link' => '/services/city-to-city-transportation/', 'icon' => 'route', 'image' => ''],
            ['title' => 'Privé sightseeing', 'kicker' => 'Nederland, op uw manier', 'description' => 'Persoonlijke sightseeing per privéauto, met de vrijheid om iedere stop aan uw planning aan te passen.', 'link' => '/services/sightseeing-tours/', 'icon' => 'compass', 'image' => ''],
        ];

        $trustItemsEn = [
            ['title' => '24/7 availability', 'text' => 'Early departures and late arrivals included.', 'icon' => 'clock'],
            ['title' => 'Flight-aware', 'text' => 'We follow the details that keep airport timing moving.', 'icon' => 'plane'],
            ['title' => 'Since 2003', 'text' => 'More than two decades of private transport experience.', 'icon' => 'shield'],
            ['title' => 'Door to door', 'text' => 'A personal pickup from your exact door.', 'icon' => 'pin'],
        ];

        $trustItemsNl = [
            ['title' => '24/7 beschikbaar', 'text' => 'Ook voor vroege vluchten en late aankomsten.', 'icon' => 'clock'],
            ['title' => 'Vluchtbewust', 'text' => 'Wij letten op de details die uw luchthavenplanning soepel houden.', 'icon' => 'plane'],
            ['title' => 'Sinds 2003', 'text' => 'Meer dan twintig jaar ervaring in privévervoer.', 'icon' => 'shield'],
            ['title' => 'Van deur tot deur', 'text' => 'Persoonlijk opgehaald op het adres dat u kiest.', 'icon' => 'pin'],
        ];

        $fleetItems = [
            ['title' => 'Executive travel', 'label' => 'Refined & private', 'description' => 'A composed, comfortable cabin for airport arrivals, meetings and private city journeys.', 'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.07.jpeg'],
            ['title' => 'Premium transfers', 'label' => 'For the important details', 'description' => 'Spacious, carefully presented vehicles selected for longer journeys and VIP itineraries.', 'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.05-2.jpeg'],
            ['title' => 'Groups & luggage', 'label' => 'Room to travel well', 'description' => 'Practical, comfortable capacity for families, teams and airport transfers with extra luggage.', 'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.37-2.jpeg'],
        ];

        $fleetItemsNl = [
            ['title' => 'Directievervoer', 'label' => 'Verfijnd & privé', 'description' => 'Een rustige, comfortabele cabine voor luchthavenaankomsten, vergaderingen en privéreizen.', 'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.07.jpeg'],
            ['title' => 'Premiumtransfers', 'label' => 'Voor de belangrijke details', 'description' => 'Ruime, zorgvuldig verzorgde voertuigen voor langere reizen en VIP-programma’s.', 'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.05-2.jpeg'],
            ['title' => 'Groepen & bagage', 'label' => 'Ruimte om goed te reizen', 'description' => 'Praktische en comfortabele ruimte voor gezinnen, teams en luchthaventransfers met extra bagage.', 'image' => '/images/uploads/WhatsApp-Image-2026-01-14-at-21.05.37-2.jpeg'],
        ];

        $coverageItems = [
            ['title' => 'Drenthe', 'links' => [['label' => 'Assen', 'url' => '/taxi-assen-naar-schiphol/']]],
            ['title' => 'Flevoland', 'links' => [
                ['label' => 'Almere', 'url' => '/taxi-almere-naar-schiphol/'],
                ['label' => 'Biddinghuizen', 'url' => '/taxi-biddinghuizen-naar-schiphol/'],
                ['label' => 'Dronten', 'url' => '/taxi-dronten-naar-schiphol/'],
                ['label' => 'Emmeloord', 'url' => '/taxi-emmeloord-naar-schiphol/'],
                ['label' => 'Lelystad', 'url' => '/taxi-lelystad-naar-schiphol/'],
                ['label' => 'Zeewolde', 'url' => '/taxi-zeewolde-naar-schiphol/'],
            ]],
            ['title' => 'Gelderland', 'links' => [
                ['label' => 'Apeldoorn', 'url' => '/taxi-apeldoorn-naar-schiphol/'],
                ['label' => 'Arnhem', 'url' => '/taxi-arnhem-naar-schiphol/'],
                ['label' => 'Ermelo', 'url' => '/taxi-ermelo-naar-schiphol/'],
                ['label' => 'Lunteren', 'url' => '/taxi-lunteren-naar-schiphol/'],
            ]],
            ['title' => 'Groningen', 'links' => [['label' => 'Groningen', 'url' => '/taxi-groningen-naar-schiphol/']]],
            ['title' => 'Noord-Holland', 'links' => [
                ['label' => 'Bussum', 'url' => '/taxi-bussum-naar-schiphol/'],
                ['label' => 'Hilversum', 'url' => '/taxi-hilversum-naar-schiphol/'],
                ['label' => 'Laren', 'url' => '/taxi-laren-naar-schiphol/'],
                ['label' => 'Naarden', 'url' => '/taxi-naarden-naar-schiphol/'],
                ['label' => 'Weesp', 'url' => '/taxi-weesp-naar-schiphol/'],
            ]],
            ['title' => 'Overijssel', 'links' => [
                ['label' => 'Giethoorn', 'url' => '/taxi-giethoorn-naar-schiphol/'],
                ['label' => 'Zwolle', 'url' => '/taxi-zwolle-naar-schiphol/'],
            ]],
            ['title' => 'Utrecht', 'links' => [
                ['label' => 'Amersfoort', 'url' => '/taxi-amersfoort-naar-schiphol/'],
                ['label' => 'Baarn', 'url' => '/taxi-baarn-naar-schiphol/'],
                ['label' => 'Soest', 'url' => '/taxi-soest-naar-schiphol/'],
            ]],
        ];

        $relatedEn = [
            ['title' => 'Airport transfers', 'link' => '/services/airport-transfers/'],
            ['title' => 'VIP chauffeur', 'link' => '/services/vip-chauffeur-service/'],
            ['title' => 'Business travel', 'link' => '/services/business-transportation/'],
            ['title' => 'Private chauffeur', 'link' => '/services/private-chauffeur-service/'],
        ];
        $relatedNl = [
            ['title' => 'Luchthaventransfers', 'link' => '/services/airport-transfers/'],
            ['title' => 'VIP-chauffeur', 'link' => '/services/vip-chauffeur-service/'],
            ['title' => 'Zakelijk vervoer', 'link' => '/services/business-transportation/'],
            ['title' => 'Privéchauffeur', 'link' => '/services/private-chauffeur-service/'],
        ];

        $serviceBlock = fn (string $locale) => [
            'type' => 'service_grid',
            'content' => [
                'eyebrow' => $locale === 'nl' ? 'Reizen op maat' : 'Travel, tailored',
                'heading_line_1' => $locale === 'nl' ? 'Eén standaard.' : 'One standard.',
                'heading_line_2' => $locale === 'nl' ? 'Voor iedere reis.' : 'Every kind of journey.',
                'intro' => $locale === 'nl'
                    ? 'Van een nauwkeurige luchthavenpickup tot een volle dag volgens uw eigen route: iedere reis krijgt dezelfde zorg en aandacht.'
                    : 'From a precise airport pickup to a full day on your own itinerary, each journey receives the same care and attention.',
                'link_label' => $locale === 'nl' ? 'Bekijk dienst' : 'Explore service',
                'items' => $locale === 'nl' ? $serviceItemsNl : $serviceItemsEn,
            ],
        ];

        $fleetBlock = fn (string $locale) => [
            'type' => 'fleet_grid',
            'content' => [
                'eyebrow' => $locale === 'nl' ? 'Reizen in goed gezelschap' : 'Travel in good company',
                'heading_line_1' => $locale === 'nl' ? 'Een verzorgde ruimte' : 'A considered space',
                'heading_line_2' => $locale === 'nl' ? 'voor iedere reis.' : 'for every journey.',
                'intro' => $locale === 'nl'
                    ? 'Comfortabele, zorgvuldig verzorgde voertuigen voor luchthavenaankomsten, privéreizen en zakelijk vervoer.'
                    : 'Comfortable, carefully presented vehicles for airport arrivals, private plans and business travel.',
                'link_label' => $locale === 'nl' ? 'Bekijk het wagenpark' : 'View the fleet',
                'link' => '/fleet/',
                'items' => $locale === 'nl' ? $fleetItemsNl : $fleetItems,
            ],
        ];

        $trustBlock = fn (string $locale) => [
            'type' => 'trust_strip',
            'content' => ['items' => $locale === 'nl' ? $trustItemsNl : $trustItemsEn],
        ];

        $coverageBlock = [
            'type' => 'coverage_grid',
            'content' => [
                'eyebrow' => 'Door-to-door across the Netherlands',
                'heading_line_1' => 'Your route to Schiphol,',
                'heading_line_2' => 'from where you are.',
                'intro' => 'Choose your departure city for local transfer information, or contact us for a route not listed here.',
                'footer_note' => 'Fixed transfer quotes, confirmed personally',
                'cta_label' => 'Plan a journey',
                'cta_link' => '/booking/',
                'items' => $coverageItems,
            ],
        ];
        $coverageBlockNl = [
            'type' => 'coverage_grid',
            'content' => [
                'eyebrow' => 'Van deur tot deur in heel Nederland',
                'heading_line_1' => 'Uw route naar Schiphol,',
                'heading_line_2' => 'vanuit uw eigen omgeving.',
                'intro' => 'Kies uw vertrekplaats voor lokale transferinformatie of neem contact op voor een route die hier niet bij staat.',
                'footer_note' => 'Vaste transferprijzen, persoonlijk bevestigd',
                'cta_label' => 'Reis plannen',
                'cta_link' => '/booking/',
                'items' => $coverageItems,
            ],
        ];

        $pages = Page::query()->with('translations')->get();
        foreach ($pages as $page) {
            $this->stripEmbeddedSections($page, 'en', ['our transportation services']);
            $this->stripEmbeddedSections($page, 'nl', ['onze transportdiensten', 'onze transportdiensten']);
            if ($page->slug === 'home') {
                $this->stripEmbeddedSections($page, 'en', ['workingarea']);
                $this->stripEmbeddedSections($page, 'nl', ['werkgebied']);
            }

            $blocksByLocale = ['en' => [], 'nl' => []];
            if ($page->slug === 'home') {
                $blocksByLocale['en'] = [$trustBlock('en'), $serviceBlock('en'), $fleetBlock('en'), $coverageBlock];
                $blocksByLocale['nl'] = [$trustBlock('nl'), $serviceBlock('nl'), $fleetBlock('nl'), $coverageBlockNl];
            } elseif ($page->slug === 'services' || str_starts_with($page->slug, 'services/')) {
                $blocksByLocale['en'] = [$serviceBlock('en')];
                $blocksByLocale['nl'] = [$serviceBlock('nl')];
            } elseif ($page->slug === 'fleet') {
                $blocksByLocale['en'] = [$fleetBlock('en')];
                $blocksByLocale['nl'] = [$fleetBlock('nl')];
            } elseif ($page->template === 'landing' || $page->slug === 'rates') {
                $blocksByLocale['en'] = [[
                    'type' => 'related_links',
                    'content' => ['eyebrow' => 'Continue exploring', 'heading' => 'Other ways to travel.', 'items' => $relatedEn],
                ]];
                $blocksByLocale['nl'] = [[
                    'type' => 'related_links',
                    'content' => ['eyebrow' => 'Verder ontdekken', 'heading' => 'Andere manieren om te reizen.', 'items' => $relatedNl],
                ]];
            }

            foreach ($blocksByLocale as $locale => $blocks) {
                $page->blocks()->where('locale', $locale)->delete();
                foreach ($blocks as $index => $block) {
                    PageBlock::query()->create([
                        'page_id' => $page->id,
                        'locale' => $locale,
                        'type' => $block['type'],
                        'content' => $block['content'],
                        'sort_order' => ($index + 1) * 10,
                        'is_active' => true,
                    ]);
                }
            }
        }
    }

    private function stripEmbeddedSections(Page $page, string $locale, array $markers): void
    {
        $html = $locale === 'en'
            ? $page->content_html
            : $page->translation('nl')?->content_html;

        if (! $html) {
            return;
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
            return;
        }

        $this->styleBuilderLabels($document, $root);

        $started = false;
        foreach (iterator_to_array($root->childNodes) as $node) {
            if (! $started) {
                $text = mb_strtolower(trim($node->textContent ?? ''));
                $isMarker = in_array($text, $markers, true);
                if ($node instanceof DOMElement && $node->tagName === 'h2') {
                    $isMarker = in_array($text, $markers, true);
                }
                if ($isMarker) {
                    $started = true;
                }
            }

            if ($started) {
                $root->removeChild($node);
            }
        }

        $heroTitle = $locale === 'en'
            ? $page->hero_title
            : $page->translation('nl')?->hero_title;
        $this->normalizeArticleMedia($document, $root, $page->hero_image, $heroTitle, $page->title);

        $clean = $document->saveHTML($root) ?: '';
        $clean = preg_replace('/^<div id="uts-content">|<\/div>$/u', '', trim($clean)) ?? '';

        if ($locale === 'en') {
            $page->update(['content_html' => $clean]);
        } else {
            $page->translation('nl')?->update(['content_html' => $clean]);
        }
    }

    private function styleBuilderLabels(DOMDocument $document, DOMNode $root): void
    {
        $labels = [
            'ABOUT US', 'WHY CHOOSE US', 'BOOKING', 'JOURNEY', 'WHY CHOOSE',
            'AIRPORT FOCUS', 'SERVICES', 'OVER ONS', 'WAAROM VOOR ONS KIEZEN',
            'BOEKEN', 'REIS', 'LUCHTHAVENFOCUS',
        ];

        foreach (iterator_to_array($root->childNodes) as $node) {
            if ($node->nodeType !== XML_TEXT_NODE) {
                continue;
            }

            $label = preg_replace('/\s+/u', ' ', trim($node->textContent ?? ''));
            if (! in_array($label, $labels, true)) {
                continue;
            }

            $paragraph = $document->createElement('p');
            $paragraph->setAttribute('class', 'article-kicker');
            $paragraph->appendChild($document->createTextNode($label));
            $root->replaceChild($paragraph, $node);
        }
    }

    private function normalizeArticleMedia(
        DOMDocument $document,
        DOMElement $root,
        ?string $heroImage,
        ?string $heroTitle,
        ?string $pageTitle
    ): void {
        foreach (iterator_to_array($root->getElementsByTagName('figure')) as $figure) {
            $ancestor = $figure->parentNode;
            while ($ancestor) {
                if ($ancestor instanceof DOMElement && str_contains($ancestor->getAttribute('class'), 'article-media')) {
                    while ($figure->firstChild) {
                        $ancestor->insertBefore($figure->firstChild, $figure);
                    }
                    $figure->parentNode?->removeChild($figure);
                    break;
                }
                $ancestor = $ancestor->parentNode;
            }
        }

        $firstHeading = $root->getElementsByTagName('h2')->item(0);
        if ($firstHeading) {
            $headingText = preg_replace('/\s+/u', ' ', trim($firstHeading->textContent)) ?? '';
            $titles = array_filter([
                preg_replace('/\s+/u', ' ', trim($heroTitle ?? '')) ?? '',
                preg_replace('/\s+/u', ' ', trim($pageTitle ?? '')) ?? '',
            ]);
            if (in_array(mb_strtolower($headingText), array_map('mb_strtolower', $titles), true)) {
                $firstHeading->parentNode?->removeChild($firstHeading);
            }
        }

        $sharedImages = [
            '/images/uploads/Frame-1000006315.svg',
            '/images/uploads/WhatsApp-Image-2026-01-14-at-21.06.07.jpeg',
        ];

        foreach (iterator_to_array($root->getElementsByTagName('img')) as $image) {
            $src = $image->getAttribute('src');
            if (in_array($src, $sharedImages, true) || ($heroImage && $src === $heroImage)) {
                $parent = $image->parentNode;
                $image->parentNode?->removeChild($image);
                if ($parent instanceof DOMElement && trim($parent->textContent) === '') {
                    $parent->parentNode?->removeChild($parent);
                }

                continue;
            }

            $imagePath = public_path(ltrim($src, '/'));
            if (is_file($imagePath)) {
                $dimensions = @getimagesize($imagePath);
                if ($dimensions) {
                    $image->setAttribute('width', (string) $dimensions[0]);
                    $image->setAttribute('height', (string) $dimensions[1]);
                }
            }

            $parent = $image->parentNode;
            if (! $parent) {
                continue;
            }
            if ($parent instanceof DOMElement && str_contains($parent->getAttribute('class'), 'article-media')) {
                continue;
            }

            $figure = $document->createElement('figure');
            $figure->setAttribute('class', 'article-media');
            $figure->appendChild($image->cloneNode(true));
            $parent->replaceChild($figure, $image);

            $alt = trim($image->getAttribute('alt'));
            if ($alt !== '') {
                $caption = $document->createElement('figcaption');
                $caption->appendChild($document->createTextNode($alt));
                $figure->appendChild($caption);
            }
        }

        foreach (iterator_to_array($root->getElementsByTagName('strong')) as $strong) {
            if (mb_strlen(trim($strong->textContent)) < 180) {
                continue;
            }

            $parentTag = $strong->parentNode instanceof DOMElement
                ? strtolower($strong->parentNode->tagName)
                : '';
            if (in_array($parentTag, ['h2', 'h3', 'h4'], true)) {
                continue;
            }

            while ($strong->firstChild) {
                $strong->parentNode->insertBefore($strong->firstChild, $strong);
            }
            $strong->parentNode->removeChild($strong);
        }
    }
}
