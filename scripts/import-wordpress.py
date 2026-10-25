#!/usr/bin/env python3
"""Import the public WordPress pages, SEO metadata and media into local app data.

The source site renders its page builder through WordPress REST, so the page
body is read from the live public page and reduced to semantic HTML. This keeps
the visible copy while removing theme markup, scripts and builder attributes.
"""

from __future__ import annotations

import json
import re
import sys
import time
import xml.etree.ElementTree as ET
from pathlib import Path
from urllib.parse import unquote, urljoin, urlparse

import requests
from bs4 import BeautifulSoup, Comment, Tag


ROOT = Path(__file__).resolve().parents[1]
DATA_DIR = ROOT / "database" / "data"
UPLOAD_DIR = ROOT / "public" / "images" / "uploads"
BASE_URL = "https://urgent-travelservices.nl"
SITEMAP_URL = f"{BASE_URL}/page-sitemap.xml"
USER_AGENT = "UrgentTravelServicesMigration/1.0 (+https://urgent-travelservices.nl)"

ALLOWED_TAGS = {
    "a",
    "blockquote",
    "br",
    "em",
    "figcaption",
    "figure",
    "h2",
    "h3",
    "h4",
    "h5",
    "h6",
    "hr",
    "img",
    "li",
    "ol",
    "p",
    "span",
    "strong",
    "table",
    "tbody",
    "td",
    "th",
    "thead",
    "tr",
    "ul",
}

SAFE_ATTRIBUTES = {"alt", "href", "loading", "rel", "src", "target", "title"}
IMAGE_EXTENSIONS = {".jpg", ".jpeg", ".png", ".svg", ".webp", ".gif", ".avif"}

HERO_IMAGES = {
    "home": "WhatsApp-Image-2026-01-14-at-21.05.57.jpeg",
    "services/business-transportation": "WhatsApp-Image-2026-01-14-at-21.05.37.jpeg",
    "services/airport-transfers": "WhatsApp-Image-2026-01-14-at-21.06.04.jpeg",
    "services/sightseeing-tours": "keukenhof.jpg",
    "services/vip-chauffeur-service": "WhatsApp-Image-2026-01-14-at-21.06.05-2.jpeg",
    "services/city-to-city-transportation": "WhatsApp-Image-2026-01-14-at-21.05.37-1.jpeg",
    "services/private-chauffeur-service": "WhatsApp-Image-2026-01-14-at-21.05.38-4.jpeg",
    "fleet": "WhatsApp-Image-2026-01-14-at-21.05.37-2.jpeg",
    "landing": "WhatsApp-Image-2026-01-14-at-21.05.38-4.jpeg",
}


def log(message: str) -> None:
    print(message, flush=True)


def request(session: requests.Session, url: str, **kwargs) -> requests.Response:
    last_error: Exception | None = None
    for attempt in range(3):
        try:
            response = session.get(url, timeout=45, **kwargs)
            response.raise_for_status()
            return response
        except requests.RequestException as error:
            last_error = error
            if attempt < 2:
                time.sleep(1.5 * (attempt + 1))
    raise RuntimeError(f"Could not download {url}: {last_error}")


def get_sitemap_urls(session: requests.Session) -> list[str]:
    response = request(session, SITEMAP_URL)
    root = ET.fromstring(response.content)
    return [
        element.text.strip()
        for element in root.findall("{http://www.sitemaps.org/schemas/sitemap/0.9}url/{http://www.sitemaps.org/schemas/sitemap/0.9}loc")
        if element.text
    ]


def get_wordpress_pages(session: requests.Session) -> list[dict]:
    response = request(
        session,
        f"{BASE_URL}/wp-json/wp/v2/pages",
        params={
            "per_page": 100,
            "status": "publish",
            "_fields": "id,slug,link,title,content,excerpt,date,modified,featured_media,yoast_head_json",
        },
    )
    return response.json()


def get_wordpress_media(session: requests.Session) -> list[dict]:
    media: list[dict] = []
    page = 1
    while True:
        response = request(
            session,
            f"{BASE_URL}/wp-json/wp/v2/media",
            params={
                "per_page": 100,
                "page": page,
                "_fields": "id,source_url,mime_type,title,alt_text,media_details",
            },
        )
        batch = response.json()
        media.extend(batch)
        if page >= int(response.headers.get("X-WP-TotalPages", page)) or not batch:
            return media
        page += 1


def local_image_path(url: str) -> str:
    clean_url = unquote(urlparse(url).path)
    filename = Path(clean_url).name
    return f"/images/uploads/{filename}"


def download_asset(session: requests.Session, url: str) -> str | None:
    parsed = urlparse(url)
    if parsed.scheme not in {"http", "https"}:
        return None
    filename = Path(unquote(parsed.path)).name
    if not filename:
        return None
    suffix = Path(filename).suffix.lower()
    if suffix not in IMAGE_EXTENSIONS:
        return None
    target = UPLOAD_DIR / filename
    if target.exists() and target.stat().st_size > 0:
        return local_image_path(url)
    response = request(session, url)
    content_type = response.headers.get("content-type", "").lower()
    if not content_type.startswith("image/"):
        return None
    target.write_bytes(response.content)
    log(f"  downloaded {filename} ({len(response.content) // 1024} KB)")
    return local_image_path(url)


def clean_text(value: str) -> str:
    return re.sub(r"\s+", " ", value or "").strip()


def meta_content(soup: BeautifulSoup, *, name: str | None = None, prop: str | None = None) -> str | None:
    if name:
        tag = soup.find("meta", attrs={"name": name})
    else:
        tag = soup.find("meta", attrs={"property": prop})
    return clean_text(tag.get("content", "")) if tag else None


def sanitize_main(main: Tag, image_map: dict[str, str]) -> str:
    main = BeautifulSoup(str(main), "html.parser")

    for unwanted in main.find_all(["script", "style", "noscript", "iframe", "form", "svg"]):
        unwanted.decompose()
    for comment in main.find_all(string=lambda text: isinstance(text, Comment)):
        comment.extract()
    for unwanted in main.select(
        ".nectar-global-section, .nectar-skip-to-content, .screen-reader-text, "
        ".nectar-button, .wpb_video_widget, .nectar-fancy-box, .nectar-cta, "
        ".nectar-video-self-hosted, .nectar-scroll-to-top, .breadcrumb, .yoast"
    ):
        unwanted.decompose()

    for image in main.find_all("img"):
        src = (
            image.get("src")
            or image.get("data-src")
            or image.get("data-lazy-src")
            or image.get("data-original")
        )
        if not src:
            image.decompose()
            continue
        absolute = urljoin(BASE_URL, src)
        local = image_map.get(absolute) or image_map.get(absolute.split("?")[0])
        if not local:
            local = local_image_path(absolute)
        image["src"] = local
        image["loading"] = "lazy"
        image.attrs = {key: value for key, value in image.attrs.items() if key in SAFE_ATTRIBUTES}

    for link in main.find_all("a"):
        href = link.get("href", "")
        if href.startswith(BASE_URL):
            href = href[len(BASE_URL) :] or "/"
        link["href"] = href
        link.attrs = {key: value for key, value in link.attrs.items() if key in SAFE_ATTRIBUTES}

    first_h1 = main.find("h1")
    if first_h1:
        first_h1.name = "h2"

    for tag in list(main.find_all(True)):
        if tag.name == "html":
            continue
        if tag.name not in ALLOWED_TAGS:
            tag.unwrap()
            continue
        tag.attrs = {key: value for key, value in tag.attrs.items() if key in SAFE_ATTRIBUTES}
        if tag.name not in {"a", "img", "br", "hr"} and not clean_text(tag.get_text(" ")):
            tag.decompose()

    body = main.find("body")
    return body.decode_contents() if body else main.decode_contents()


def page_template(slug: str) -> str:
    if slug == "home":
        return "home"
    if slug == "services":
        return "services"
    if slug.startswith("services/"):
        return "service"
    if slug in {"contact", "booking", "rates", "fleet"}:
        return slug
    if slug.startswith("taxi-") or slug in {"schiphol-taxi"}:
        return "landing"
    return "general"


def hero_eyebrow(template: str, slug: str) -> str:
    if template == "home":
        return "24/7 Schiphol & executive chauffeur service"
    if template == "landing":
        city = slug.removeprefix("taxi-").removesuffix("-naar-schiphol").replace("-", " ").title()
        return f"Private transfer to Schiphol from {city}"
    return {
        "services": "Our services",
        "service": "Executive travel, considered",
        "rates": "Transparent pricing",
        "fleet": "The Urgent fleet",
        "booking": "Reserve your journey",
        "contact": "We are here 24/7",
        "general": "Urgent Travel Services",
    }.get(template, "Urgent Travel Services")


def find_main(soup: BeautifulSoup) -> Tag | None:
    for selector in ("div.main-content", "main#main", "main", "article"):
        candidate = soup.select_one(selector)
        if candidate and len(clean_text(candidate.get_text(" "))) > 80:
            return candidate
    return soup.select_one("div.main-content") or soup.body


def main() -> None:
    DATA_DIR.mkdir(parents=True, exist_ok=True)
    UPLOAD_DIR.mkdir(parents=True, exist_ok=True)
    session = requests.Session()
    session.headers["User-Agent"] = USER_AGENT

    log("Reading page sitemap...")
    sitemap_urls = get_sitemap_urls(session)
    log(f"Found {len(sitemap_urls)} published pages.")

    log("Reading WordPress page metadata...")
    wp_pages = get_wordpress_pages(session)
    wp_by_url = {page["link"].rstrip("/") + "/": page for page in wp_pages}
    wp_by_slug = {page["slug"]: page for page in wp_pages}

    log("Downloading WordPress media library...")
    media = get_wordpress_media(session)
    image_map: dict[str, str] = {}
    for item in media:
        if not str(item.get("mime_type", "")).startswith("image/"):
            continue
        source_url = item.get("source_url")
        if not source_url:
            continue
        local = download_asset(session, source_url)
        if local:
            image_map[source_url] = local

    pages: list[dict] = []
    for index, url in enumerate(sitemap_urls, start=1):
        slug = urlparse(url).path.strip("/") or "home"
        log(f"[{index}/{len(sitemap_urls)}] {slug}")
        response = request(session, url)
        soup = BeautifulSoup(response.text, "html.parser")

        main_node = find_main(soup)
        if not main_node:
            raise RuntimeError(f"No content container found for {url}")

        # Gather all image variants referenced by this page before sanitizing it.
        for image in main_node.find_all("img"):
            src = (
                image.get("src")
                or image.get("data-src")
                or image.get("data-lazy-src")
                or image.get("data-original")
            )
            if not src:
                continue
            absolute = urljoin(BASE_URL, src).split("?")[0]
            if absolute not in image_map:
                local = download_asset(session, absolute)
                if local:
                    image_map[absolute] = local

        wp_page = wp_by_url.get(url) or wp_by_slug.get(slug)
        yoast = (wp_page or {}).get("yoast_head_json", {})
        canonical = yoast.get("canonical") or soup.find("link", rel="canonical").get("href", url)
        title_tag = soup.find("title")
        seo_title = yoast.get("title") or clean_text(title_tag.get_text()) if title_tag else slug
        seo_description = yoast.get("description") or meta_content(soup, name="description") or ""
        robots_meta = meta_content(soup, name="robots") or "index, follow"

        first_heading = main_node.find("h1") or main_node.find("h2") or main_node.find("h3")
        page_title = clean_text(first_heading.get_text(" ")) if first_heading else ""
        if not page_title and wp_page:
            page_title = clean_text(BeautifulSoup(wp_page["title"]["rendered"], "html.parser").get_text(" "))
        page_title = page_title or slug.replace("-", " ").title()

        first_paragraph = main_node.find("p")
        paragraph_text = clean_text(first_paragraph.get_text(" ")) if first_paragraph else ""
        subtitle = seo_description or paragraph_text

        page_images = []
        for image in main_node.find_all("img"):
            src = image.get("src") or image.get("data-src")
            if not src:
                continue
            local = image_map.get(urljoin(BASE_URL, src).split("?")[0])
            if local and local not in page_images:
                page_images.append(local)
        content_html = sanitize_main(main_node, image_map)
        template = page_template(slug)
        preferred_hero = HERO_IMAGES.get(slug) or HERO_IMAGES.get(template)
        hero_image = None
        if preferred_hero and (UPLOAD_DIR / preferred_hero).exists():
            hero_image = f"/images/uploads/{preferred_hero}"
        if not hero_image:
            hero_image = next(
                (
                    path
                    for path in page_images
                    if Path(path).suffix.lower() in {".jpg", ".jpeg", ".png", ".webp"}
                    and "logo" not in path.lower()
                ),
                page_images[0] if page_images else None,
            )
        excerpt = seo_description or paragraph_text[:250]
        schema = yoast.get("schema")

        pages.append(
            {
                "wordpress_id": (wp_page or {}).get("id"),
                "slug": slug,
                "path": urlparse(url).path or "/",
                "title": page_title,
                "template": template,
                "hero_eyebrow": hero_eyebrow(template, slug),
                "hero_title": page_title,
                "hero_subtitle": subtitle,
                "hero_image": hero_image,
                "excerpt": excerpt,
                "content_html": content_html,
                "images": page_images,
                "seo_title": seo_title,
                "seo_description": seo_description,
                "focus_keyword": "",
                "canonical_url": canonical,
                "robots": robots_meta,
                "og_title": yoast.get("og_title") or seo_title,
                "og_description": yoast.get("og_description") or seo_description,
                "og_image": image_map.get(yoast.get("og_image", ""), hero_image),
                "schema_json": schema,
                "published_at": (wp_page or {}).get("date"),
                "modified_at": (wp_page or {}).get("modified"),
                "sort_order": index,
                "is_published": True,
            }
        )

    # Persist any additional image variants encountered in the raw source pages.
    for raw_name in ("current-home.html",):
        raw_path = ROOT / raw_name
        if not raw_path.exists():
            continue
        raw = raw_path.read_text(encoding="utf-8", errors="ignore")
        for url in re.findall(r"https://urgent-travelservices\.nl/wp-content/uploads/[^\"' )<]+", raw):
            absolute = url.split("?")[0]
            if absolute not in image_map:
                local = download_asset(session, absolute)
                if local:
                    image_map[absolute] = local

    image_manifest = [
        {"source_url": source, "local_path": local}
        for source, local in sorted(image_map.items())
    ]
    payload = {
        "source": BASE_URL,
        "imported_at": time.strftime("%Y-%m-%dT%H:%M:%SZ", time.gmtime()),
        "pages": pages,
        "images": image_manifest,
    }
    output = DATA_DIR / "wordpress-pages.json"
    output.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding="utf-8")
    log(f"Wrote {len(pages)} pages and {len(image_manifest)} images to {output.relative_to(ROOT)}")


if __name__ == "__main__":
    try:
        main()
    except Exception as exc:
        print(f"Import failed: {exc}", file=sys.stderr)
        raise
