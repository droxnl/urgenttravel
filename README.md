# Urgent Travel Services

A complete Laravel 12 and Vue 3 website and content manager for Urgent Travel Services. The application focuses on Schiphol transfers and executive chauffeur travel while preserving the existing page inventory, editorial copy, Yoast SEO metadata, schema, and locally hosted media from `urgent-travelservices.nl`.

## What is included

- A single Laravel project serving both the public Vue application and the Laravel API.
- All 39 published pages from the source sitemap.
- 93 locally stored source images in `public/images/uploads`.
- Preserved page titles, descriptions, canonicals, robots directives, Open Graph metadata, and JSON-LD schemas for the existing pages.
- Complete Dutch versions of all pages under `/nl/`, with translated SEO, schema and transactional emails.
- A flag-only language switcher that preserves the current page in both desktop and mobile navigation.
- A responsive Schiphol-first design with navy/charcoal, ivory, and muted-gold styling.
- A four-step booking wizard with route mapping, live fare estimates, vehicle filtering, optional extras and payment preference.
- A working booking/contact inquiry workflow stored in the database.
- Admin-managed vehicle fares, hourly rates, capacities, images and booking extras.
- Hostinger Mail API notifications for the team and request-received confirmations for customers.
- Delivery status and provider errors logged against each inquiry.
- A protected content manager at `/admin`.
- Rich-text page editing, hero copy/media editing, SEO editing, schema editing, and publishing controls.
- Structured page blocks for service cards, fleet cards, trust points, coverage groups and related links.
- Per-item block editing with add, remove, reorder and activate controls.
- A local media library with upload support.
- Configurable OpenAI copy generation and optional SEO generation on page save.
- XML sitemap and robots endpoints.

## Requirements

- PHP 8.2 or newer with PDO MySQL
- MySQL 8.0 or newer
- Composer 2
- Node.js 20 or newer
- npm

## Local installation

```bash
composer install
cp .env.example .env
php artisan key:generate
npm install
php artisan migrate --seed
npm run build
php artisan serve
```

On Windows PowerShell, use `Copy-Item .env.example .env` instead of `cp`.

The local site is available at `http://127.0.0.1:8000` by default. If that port is occupied, use another port:

```bash
php artisan serve --port=8787
```

## MySQL database

This application uses MySQL, not SQLite. Configure the database in `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=urgent_travel_services
DB_USERNAME=urgent_travel
DB_PASSWORD=your-password
DB_ENGINE=InnoDB
```

Create the database and application user before the first migration:

```sql
CREATE DATABASE urgent_travel_services
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
CREATE USER 'urgent_travel'@'localhost'
    IDENTIFIED BY 'your-password';
GRANT ALL PRIVILEGES ON urgent_travel_services.*
    TO 'urgent_travel'@'localhost';
```

The test suite uses a separate `urgent_travel_services_test` database so it never modifies application data.

## Content manager

Open `/admin/login`.

The seeded local credentials come from `.env`:

```dotenv
ADMIN_EMAIL=admin@urgent-travelservices.nl
ADMIN_PASSWORD=ChangeMe123!
```

Change `ADMIN_PASSWORD` before using this application on a public server, then run:

```bash
php artisan db:seed --class=DatabaseSeeder
```

The content manager includes:

- **Pages:** all imported pages, with template, path, SEO status, and publication status.
- **Page editor:** rich-text body, hero content, hero image, SEO, Open Graph, canonical URL, JSON-LD, URL, and sorting.
- **Media library:** browse the migrated images or upload a new hero image.
- **Inquiries:** booking and contact requests, status changes, email reply, and deletion.
- **Booking & tariffs:** vehicle names, descriptions, images, passenger/luggage capacity, kilometre rates, optional hourly rates, visibility, ordering, and per-item or per-booking extras.
- **Settings, email & AI:** business details, encrypted Hostinger Mail API credentials, notification recipients, encrypted OpenAI API key, model, endpoint, language, tone, system instructions, and optional automatic SEO.

## Booking calculator

The booking flow provides free type-ahead address suggestions through Photon, calculates the final driving route through OpenStreetMap Nominatim and OSRM, then displays a map, road distance, drive time and a price estimate for each suitable vehicle. Address searches and lookups are cached.

The initial fares are:

- Normal car: €1.80 per km
- Business car, E-Class or similar: €2.15 per km
- Van, V-Class/Vito or similar: €2.80 per km
- S-Class/EQS/A8/7 Series: €5.00 per km
- Child seat: €15.00 each
- Booster seat: €5.00 each
- Pickup inside at the gate: €25.00 per booking

Return journeys charge the road distance twice. Hourly hire uses the optional hourly rates configured per vehicle; if a vehicle has no hourly rate, the website shows “Price on request” instead of inventing one. Final totals are always recalculated by Laravel when the request is submitted.

Open **Admin → Booking & tariffs** to change all vehicle and extra values. The public route services can be overridden with:

```dotenv
BOOKING_GEOCODING_URL=https://nominatim.openstreetmap.org/search
BOOKING_GEOCODING_USER_AGENT="UrgentTravelServices/1.0 (info@urgent-travelservices.nl)"
BOOKING_ADDRESS_SEARCH_URL=https://photon.komoot.io/api
BOOKING_ROUTE_URL=https://router.project-osrm.org
```

## Hostinger email notifications

The application uses the dedicated Hostinger Mail API at `https://api.mail.hostinger.com`, not the hosting API or mailbox SMTP password.

1. In Hostinger hPanel, open **Emails → Agentic Mail → API access**.
2. Create a token scoped to the mailbox that will send notifications.
3. Open **Admin → Settings, email & AI → Email notifications**.
4. Enable notifications and paste the API token.
5. Save, then select **Verify account & load mailboxes**.
6. Choose the sender mailbox and confirm the team notification email.
7. Enable the team alert and/or customer confirmation, save again, and send a test email.

The Mail API token is encrypted and is never returned to the browser after saving. The mailbox resource ID normally begins with `AC`. The token must grant the relevant actions to the selected mailbox.

Environment-based configuration is also supported:

```dotenv
HOSTINGER_MAIL_API_TOKEN=
HOSTINGER_MAILBOX_RESOURCE_ID=
HOSTINGER_MAIL_API_BASE_URL=https://api.mail.hostinger.com/api/v1
```

For every submitted request, the application can send:

- A detailed new-request alert to the business notification address.
- A branded request-received confirmation to the customer, clearly stating that availability and pricing are still to be confirmed.

Email sends do not cause a customer's booking request to fail if Hostinger is temporarily unavailable. The failed delivery and provider error are recorded against the inquiry in the admin inbox.

## OpenAI configuration

OpenAI is disabled until it is configured. It can be configured in two ways.

### Through the admin interface

1. Open **Settings & AI**.
2. Open the **OpenAI** tab.
3. Enable the assistant.
4. Enter an API key.
5. Select a model, language, and tone.
6. Save, then test the connection.

The key is encrypted with Laravel's application key and is never returned to the browser after saving.

### Through environment variables

```dotenv
OPENAI_ENABLED=true
OPENAI_API_KEY=sk-...
OPENAI_MODEL=gpt-4o-mini
OPENAI_BASE_URL=https://api.openai.com
```

AI output is treated as a draft. A person must review and save it before it appears on the website. Automatic SEO can also be enabled globally in settings and per page when saving.

## Re-importing the WordPress content

The imported snapshot is committed in `database/data/wordpress-pages.json`. To refresh it from the public WordPress API:

```bash
python -m pip install -r scripts/requirements.txt
python scripts/import-wordpress.py
php artisan db:seed --force
```

The importer:

- reads `https://urgent-travelservices.nl/page-sitemap.xml`;
- retrieves WordPress page data and Yoast metadata;
- captures all public-media image assets;
- converts page-builder output to safe semantic HTML;
- downloads media to `public/images/uploads`;
- rewrites content image URLs to local paths.

Re-running the importer intentionally refreshes imported page content. Any subsequent edits in the content manager should be backed up first.

## Languages

English remains available at `/`. The full Dutch site is available under `/nl/`, for example:

- `/nl/`
- `/nl/services/airport-transfers/`
- `/nl/taxi-almere-naar-schiphol/`
- `/nl/booking/`

Both language versions receive canonical URLs, `hreflang` links and sitemap entries. The language switcher shows only the Dutch and British flags; the active language is indicated by the highlighted flag.

The Dutch translation snapshot is stored in `database/data/wordpress-pages-nl.json`. To regenerate it:

```bash
python scripts/translate-pages-nl.py
php artisan db:seed --class=PageTranslationSeeder
```

Dutch page content, hero copy, SEO and schema can also be edited in the content manager by opening a page and selecting the **Nederlands** tab.

## Structured page blocks

Repeated card and grid sections are stored in the `page_blocks` table rather than dumped into the rich-text body. This keeps the homepage and service pages visually consistent and makes each item independently editable.

Open **Admin → Pages → [page] → Structured blocks** to edit:

- Trust points
- Service cards
- Fleet cards
- Province and city coverage groups
- Related service links

Each block also has independent English and Dutch content. Use the **Nederlands** tab to edit the Dutch block items.

## Main application structure

```text
app/Http/Controllers/Api/        Laravel APIs for pages, bookings, admin, media, and settings
app/Models/                     Page, SiteSetting, BookingInquiry, User
app/Services/OpenAiService.php  Configurable OpenAI copy workflow
app/Services/HostingerMailService.php  Hostinger Mail API transport
app/Services/BookingNotifier.php       Booking and customer email composition
app/Support/HtmlSanitizer.php   Allow-list sanitizer for edited page HTML
resources/js/views/             Public Vue pages
resources/js/views/admin/       Vue content-manager screens
resources/js/components/        Shared Vue interface components
resources/css/                  Core, public, and admin design systems
public/images/uploads/          Migrated and newly uploaded media
database/data/                  Reproducible WordPress import snapshot
scripts/import-wordpress.py     WordPress content and image importer
scripts/translate-pages-nl.py   HTML-aware Dutch content generator
```

## Verification

```bash
php artisan test
vendor/bin/pint --test
npm run build
```

The included feature tests cover page availability and SEO, the XML sitemap, route quotes, vehicle and extra fares, server-side price recalculation, booking submissions, tariff administration, admin authentication, page editing, HTML sanitization, secret handling, Hostinger mailbox discovery, Hostinger sending, and delivery logging.

## Deployment notes

- Set `APP_ENV=production`, `APP_DEBUG=false`, and the final `APP_URL`.
- Use a production database and set a strong `ADMIN_PASSWORD`.
- Run `php artisan config:cache`, `php artisan route:cache`, and `npm run build`.
- Point the web server document root to `public`.
- The source-site canonical URLs are preserved for SEO. Update them page-by-page if the public domain changes.
- Review the migrated copy with the business owner before launch. The source site contains some courier/logistics wording on older rates and fleet pages; the new visual system and homepage are Schiphol/chauffeur focused, but all existing editorial text remains available for editing.
