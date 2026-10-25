<?php

namespace Database\Seeders;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(WordPressContentSeeder::class);
        $this->call(PageTranslationSeeder::class);
        $this->call(PageBlockSeeder::class);
        $this->call(BookingCatalogSeeder::class);

        User::query()->updateOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@urgent-travelservices.nl'),
        ], [
            'name' => 'Urgent Travel Admin',
            'password' => env('ADMIN_PASSWORD', 'ChangeMe123!'),
        ]);

        $settings = [
            'site_name' => 'Urgent Travel Services',
            'site_tagline' => '24/7 VIP chauffeur & Schiphol airport transfers',
            'phone' => '+31641108800',
            'whatsapp' => '31641108800',
            'email' => 'info@urgent-travelservices.nl',
            'address' => 'Jacob van Ruysdealstraat 170',
            'city' => 'Almere',
            'postcode' => '1318 NT',
            'country' => 'The Netherlands',
            'kvk' => '65672828',
            'vat' => 'NL648257654B03',
            'booking_url' => '/booking',
            'mail_enabled' => 0,
            'hostinger_mailbox_resource_id' => env('HOSTINGER_MAILBOX_RESOURCE_ID'),
            'mail_sender_name' => 'Urgent Travel Services',
            'mail_sender_email' => 'info@urgent-travelservices.nl',
            'mail_notification_email' => 'info@urgent-travelservices.nl',
            'mail_send_admin' => 1,
            'mail_send_customer' => 1,
            'openai_enabled' => (int) env('OPENAI_ENABLED', false),
            'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'openai_base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com'),
            'openai_language' => 'en',
            'openai_tone' => 'premium, calm, dependable, concise',
            'openai_auto_seo' => 0,
            'openai_system_prompt' => 'You are the senior copywriter for Urgent Travel Services, a 24/7 VIP chauffeur and Schiphol airport transfer company in the Netherlands. Preserve factual claims. Never invent prices, licenses, reviews or guarantees. Write polished, specific, conversion-ready copy.',
        ];

        foreach ($settings as $key => $value) {
            if (SiteSetting::query()->where('key', $key)->doesntExist()) {
                SiteSetting::put($key, $value);
            }
        }
    }
}
