#!/usr/bin/env python3
"""Create the Dutch page snapshot from the imported English content.

Translation is performed in HTML-aware chunks so links, media and semantic
markup remain intact. The resulting JSON is versioned in the repository and is
the source used by the database seeder.
"""

from __future__ import annotations

import copy
import html as html_lib
import json
import time
from pathlib import Path

import requests
from bs4 import BeautifulSoup, Tag


ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "database" / "data" / "wordpress-pages.json"
OUTPUT = ROOT / "database" / "data" / "wordpress-pages-nl.json"
BASE_URL = "https://urgent-travelservices.nl"
TRANSLATE_URL = "https://translate.googleapis.com/translate_a/single"

SEO_OVERRIDES = {
    "home": {
        "seo-title": "VIP-chauffeursservice Amsterdam | 24/7 vervoer – Urgent Travel Services",
    },
    "services": {
        "seo-title": "Diensten | Executive chauffeur & luchthaventransfers",
        "seo-description": "Luchthaventransfers, zakelijk vervoer, privéchauffeur en city-to-city reizen met Urgent Travel Services.",
    },
    "booking": {
        "seo-title": "Transfer boeken | Urgent Travel Services",
        "seo-description": "Vraag online een privétransfer of chauffeur aan. Het team bevestigt beschikbaarheid en prijs persoonlijk.",
    },
    "services/business-transportation": {
        "seo-title": "Zakelijk chauffeursvervoer Amsterdam | Urgent Travel Services",
    },
    "services/vip-chauffeur-service": {
        "seo-title": "VIP-chauffeursservice Amsterdam | Luxe vervoer",
    },
    "services/private-chauffeur-service": {
        "seo-title": "Privéchauffeur Amsterdam | Luxe vervoer",
    },
    "schiphol-taxi": {
        "seo-title": "Taxi Schiphol | 24/7 luchthaventransfers",
        "seo-description": "Reserveer een betrouwbare taxi naar of van Schiphol met een professionele chauffeur en persoonlijke bevestiging.",
    },
    "taxi-naar-schiphol": {
        "seo-title": "Taxi naar Schiphol | Vaste prijzen en 24/7 beschikbaar",
        "seo-description": "Boek uw taxi naar Schiphol met vluchtmonitoring, vaste prijsafspraken en ophalen aan de deur.",
    },
    "taxi-met-maxicosi": {
        "seo-title": "Taxi met maxicosi naar Schiphol | Kinderzitje op aanvraag",
        "seo-description": "Reis veilig naar Schiphol met een maxicosi op aanvraag. Vermeld de leeftijd van uw kind bij de boeking.",
    },
    "taxi-met-kinderstoel": {
        "seo-title": "Taxi met kinderstoel naar Schiphol | Veilig gezinsvervoer",
        "seo-description": "Privétaxi naar Schiphol met een kinderstoel op aanvraag. Comfortabel en veilig reizen met het hele gezin.",
    },
    "taxi-op-rekening": {
        "seo-title": "Taxi op rekening | Zakelijk chauffeursvervoer",
        "seo-description": "Zakelijke taxiritten op rekening met duidelijke afspraken en persoonlijke ondersteuning van Urgent Travel Services.",
    },
    "rates": {
        "seo-title": "Tarieven | Transparante chauffeursprijzen",
        "seo-description": "Ontvang een duidelijke prijsopgave voor uw transfer. De prijs wordt vóór vertrek persoonlijk met u bevestigd.",
    },
    "fleet": {
        "seo-title": "Ons wagenpark | Comfortabel en discreet reizen",
        "seo-description": "Ontdek onze comfortabele voertuigen voor luchthaventransfers, zakelijke reizen en privéchauffeursdiensten.",
    },
}


def translate_html(html: str) -> str:
    if not html.strip():
        return html

    for attempt in range(4):
        try:
            response = requests.get(
                TRANSLATE_URL,
                params={
                    "client": "gtx",
                    "sl": "en",
                    "tl": "nl",
                    "dt": "t",
                    "q": html,
                },
                headers={"User-Agent": "Mozilla/5.0"},
                timeout=45,
            )
            response.raise_for_status()
            payload = response.json()
            translated = "".join(segment[0] for segment in payload[0] if segment and segment[0])
            if translated:
                return translated
        except (requests.RequestException, ValueError, TypeError):
            if attempt == 3:
                raise
            time.sleep(1.2 * (attempt + 1))

    return html


def html_chunks(html: str, max_length: int = 3500) -> list[str]:
    soup = BeautifulSoup(html, "html.parser")
    chunks: list[str] = []
    current: list[str] = []
    current_length = 0

    for child in list(soup.contents):
        child_html = str(child)
        if current and current_length + len(child_html) > max_length:
            chunks.append("".join(current))
            current = []
            current_length = 0
        current.append(child_html)
        current_length += len(child_html)

    if current:
        chunks.append("".join(current))

    return chunks or [html]


def translate_content(html: str) -> str:
    translated = "".join(translate_html(chunk) for chunk in html_chunks(html))
    source_soup = BeautifulSoup(html, "html.parser")
    translated_soup = BeautifulSoup(translated, "html.parser")
    source_elements = source_soup.find_all(True)
    translated_elements = translated_soup.find_all(True)

    if len(source_elements) == len(translated_elements):
        for source_element, translated_element in zip(source_elements, translated_elements):
            if source_element.name == translated_element.name:
                translated_element.attrs = copy.deepcopy(source_element.attrs)

    return str(translated_soup)


def normalize_brand_names(value: str) -> str:
    return (
        value.replace("dringende reisdiensten", "Urgent Travel Services")
        .replace("Dringende reisdiensten", "Urgent Travel Services")
        .replace("dringende reisdienst", "Urgent Travel Services")
        .replace("Spoedreisdiensten", "Urgent Travel Services")
        .replace("spoedreisdiensten", "Urgent Travel Services")
        .replace("Rotterdam The Haag", "Rotterdam Den Haag")
        .replace("The Hague Airport", "Den Haag Airport")
    )


def translate_metadata(page: dict) -> dict[str, str]:
    fields = {
        "page-title": page["title"],
        "hero-eyebrow": page["hero_eyebrow"] or "",
        "hero-title": page["hero_title"],
        "hero-subtitle": page["hero_subtitle"] or "",
        "excerpt": page["excerpt"] or "",
        "seo-title": page["seo_title"] or "",
        "seo-description": page["seo_description"] or "",
        "og-title": page["og_title"] or "",
        "og-description": page["og_description"] or "",
    }
    xml = "".join(
        f"<{name}>{html_lib.escape(value)}</{name}>"
        for name, value in fields.items()
    )
    translated_xml = translate_html(xml)
    soup = BeautifulSoup(translated_xml, "html.parser")

    result = {}
    for name in fields:
        element = soup.find(name)
        result[name] = element.get_text(" ", strip=True) if isinstance(element, Tag) else fields[name]
    return result


def localize_schema(
    schema: dict | None,
    english_title: str,
    dutch_title: str,
    english_seo_title: str,
    dutch_seo_title: str,
    english_description: str,
    dutch_description: str,
) -> dict | None:
    if not schema:
        return None

    generic_names = {
        "Home": "Home",
        "Services": "Diensten",
        "Contact": "Contact",
        "Booking": "Boeken",
        "Rates": "Tarieven",
        "Fleet": "Wagenpark",
        "Airport Transfers": "Luchthaventransfers",
        "Airport transfers": "Luchthaventransfers",
        "VIP Chauffeur services": "VIP-chauffeursdiensten",
        "VIP Chauffeur service": "VIP-chauffeursservice",
        "VIP chauffeur services": "VIP-chauffeursdiensten",
        "Business & Executive Transportation": "Zakelijk en directievervoer",
        "Business transportation": "Zakelijk vervoer",
        "Private chauffeur services": "Privéchauffeursdiensten",
        "Private chauffeur service": "Privéchauffeursservice",
        "City-to-city transportation": "Vervoer van stad tot stad",
        "City to City": "Stad naar stad",
        "Hourly & full-day chauffeur hire": "Chauffeur per uur en hele dag",
        "Sightseeing tours": "Sightseeingtours",
    }

    def localize(value):
        if isinstance(value, str):
            if value == BASE_URL or value == f"{BASE_URL}/" or value.startswith(f"{BASE_URL}/"):
                suffix = value[len(BASE_URL):]
                return f"{BASE_URL}/nl{suffix}"
            return value
        if isinstance(value, dict):
            result = {}
            for key, child in value.items():
                if key == "inLanguage":
                    result[key] = "nl-NL"
                elif key == "name" and child == english_title:
                    result[key] = dutch_title
                elif key == "name" and child == english_seo_title:
                    result[key] = dutch_seo_title
                elif key == "name" and child in generic_names:
                    result[key] = generic_names[child]
                elif key == "description" and child == english_description:
                    result[key] = dutch_description
                else:
                    result[key] = localize(child)
            return result
        if isinstance(value, list):
            return [localize(item) for item in value]
        return value

    return localize(copy.deepcopy(schema))


def main() -> None:
    source = json.loads(SOURCE.read_text(encoding="utf-8"))
    translated_pages = []

    for index, page in enumerate(source["pages"], start=1):
        print(f"[{index}/{len(source['pages'])}] Translating {page['slug']}", flush=True)
        metadata = translate_metadata(page)
        metadata.update(SEO_OVERRIDES.get(page["slug"], {}))
        metadata = {key: normalize_brand_names(value) for key, value in metadata.items()}
        content_html = translate_content(page["content_html"] or "")
        content_html = normalize_brand_names(content_html)
        path = "/nl" + (page["path"] if page["path"] != "/" else "/")
        canonical = BASE_URL + path

        translated_pages.append(
            {
                "wordpress_id": page["wordpress_id"],
                "slug": page["slug"],
                "locale": "nl",
                "path": path,
                "title": metadata["page-title"],
                "hero_eyebrow": metadata["hero-eyebrow"],
                "hero_title": metadata["hero-title"],
                "hero_subtitle": metadata["hero-subtitle"],
                "excerpt": metadata["excerpt"],
                "content_html": content_html,
                "seo_title": metadata["seo-title"],
                "seo_description": metadata["seo-description"],
                "focus_keyword": page.get("focus_keyword") or "",
                "canonical_url": canonical,
                "robots": page.get("robots") or "index, follow",
                "og_title": metadata["og-title"],
                "og_description": metadata["og-description"],
                "og_image": page.get("og_image"),
                "schema_json": localize_schema(
                    page.get("schema_json"),
                    page["title"],
                    metadata["page-title"],
                    page.get("seo_title") or "",
                    metadata["seo-title"],
                    page.get("seo_description") or "",
                    metadata["seo-description"],
                ),
                "modified_at": page.get("modified_at"),
            }
        )
        time.sleep(0.08)

    OUTPUT.write_text(
        json.dumps(
            {
                "source": "database/data/wordpress-pages.json",
                "locale": "nl",
                "pages": translated_pages,
            },
            ensure_ascii=False,
            indent=2,
        ),
        encoding="utf-8",
    )
    print(f"Wrote {len(translated_pages)} Dutch pages to {OUTPUT.relative_to(ROOT)}")


if __name__ == "__main__":
    main()
