<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian gift / favour suppliers (marketplace Gift Suppliers category).
 * Real firms found publicly (Giiftee, Giftmarttz, Just Brand, Mr Surprise) plus
 * representative gift studios; demo contact + illustrative metrics.
 * See {@see RealVendorSeeder}.
 */
class GiftSupplierVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'gift-suppliers';
    }

    protected function categoryFallbackName(): string
    {
        return 'Gift Suppliers';
    }

    protected function imagePool(): array
    {
        return [
            '1549465220-1a8b9238cd48', '1481487196290-c152efe083f5', '1607083206968-13611e3d76db',
            '1513885535751-8b9238bd345a', '1608755728617-aefab37d2edd', '1465495976277-4387d4b0b4c6',
            '1490750967868-88aa4486c946', '1478146059778-26028b07395a', '1487530811176-3780de880c2d',
        ];
    }

    protected function services(): array
    {
        return ['Wedding favours', 'Guest gift hampers', 'Corporate gifts', 'Personalised keepsakes'];
    }

    protected function packages(): array
    {
        return [
            ['Guest Favours', 300, 1000, 'per event', ['50 wedding favours', 'Custom wrapping', 'Ribbon & tag', 'Delivery']],
            ['Gift Hampers', 1200, 3000, 'per event', ['20 curated hampers', 'Branded packaging', 'Personalised cards', 'Delivery']],
            ['Bespoke Gifting', 3500, 8000, 'per event', ['Fully custom hampers', 'Premium contents', 'Logo / monogram branding', 'Bulk quantities', 'Nationwide delivery']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Gift wrapping upgrade', 'price' => 80_000], ['name' => 'Personalised message cards', 'price' => 100_000], ['name' => 'Express delivery', 'price' => 150_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Favours', 'Corporate Hampers', 'Personalised Boxes'];
    }

    protected function portfolioCaptions(): array
    {
        return ['The gifts', 'Wrapped & ready'];
    }

    protected function portfolioFeedback(): string
    {
        return 'Beautifully presented and our guests loved them - thoughtful, quality gifts delivered right on time.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'giiftee', 'email' => 'giiftee@osep.test', 'first' => 'Gloria', 'last' => 'Mushi',
             'business' => 'Giiftee Tanzania', 'tagline' => 'Tanzania’s online gifting platform', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.75, 'reviews' => 34, 'jobs' => 220, 'response' => 2,
             'website' => 'https://giiftee.com', 'instagram' => 'https://instagram.com/giiftee.tz',
             'description' => 'Tanzania’s first online gifting platform - fresh flower bouquets, artisanal cakes and curated corporate gift boxes delivered across Dar es Salaam.'],
            ['key' => 'giftmarttz', 'email' => 'giftmarttz@osep.test', 'first' => 'Frank', 'last' => 'Sanga',
             'business' => 'Giftmarttz', 'tagline' => 'Online gift shopping', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 27, 'jobs' => 160, 'response' => 3,
             'website' => 'https://giftmarttz.com', 'instagram' => 'https://instagram.com/giftmarttz',
             'description' => 'Online gift shopping in Dar es Salaam - flowers, cakes, sweets, hampers and jewels, with flower and cake delivery.'],
            ['key' => 'justbrand', 'email' => 'justbrandtz@osep.test', 'first' => 'Joseph', 'last' => 'Komba',
             'business' => 'Just Brand Tanzania', 'tagline' => 'Branded corporate gifts', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 24, 'jobs' => 140, 'response' => 3,
             'website' => 'https://www.justbrand.co.za', 'instagram' => 'https://instagram.com/justbrandtz',
             'description' => 'Supplies and delivers corporate gifts and promotional items across Tanzania, adding logos and custom messaging.'],
            ['key' => 'mrsurprise', 'email' => 'mrsurprise@osep.test', 'first' => 'Baraka', 'last' => 'Mtei',
             'business' => 'Mr Surprise TZ', 'tagline' => 'Surprise gifts & setups', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 21, 'jobs' => 120, 'response' => 3,
             'website' => 'https://mrsurprise.example', 'instagram' => 'https://instagram.com/mr_surprise_tz',
             'description' => 'Surprise gift deliveries and romantic setups for proposals, anniversaries and weddings across Dar es Salaam.'],
            ['key' => 'zawadiboxes', 'email' => 'zawadiboxes@osep.test', 'first' => 'Zawadi', 'last' => 'Ally',
             'business' => 'Zawadi Gift Boxes', 'tagline' => 'Curated gift boxes', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 4, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 72, 'response' => 3,
             'website' => 'https://zawadiboxes.example', 'instagram' => 'https://instagram.com/zawadigiftboxes',
             'description' => 'Curated gift boxes and wedding favours with beautiful, personalised packaging.'],
            ['key' => 'hamperhouse', 'email' => 'hamperhouse@osep.test', 'first' => 'Halima', 'last' => 'Juma',
             'business' => 'Hamper House TZ', 'tagline' => 'Luxury hampers', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 15, 'jobs' => 68, 'response' => 3,
             'website' => 'https://hamperhouse.example', 'instagram' => 'https://instagram.com/hamperhousetz',
             'description' => 'Luxury gift hampers for weddings and corporate occasions, filled with premium local and imported treats.'],
            ['key' => 'wrappedready', 'email' => 'wrappedready@osep.test', 'first' => 'Winnie', 'last' => 'Mollel',
             'business' => 'Wrapped & Ready', 'tagline' => 'Favours & keepsakes', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 4, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 14, 'jobs' => 60, 'response' => 4,
             'website' => 'https://wrappedready.example', 'instagram' => 'https://instagram.com/wrappedreadytz',
             'description' => 'Wedding favours and personalised keepsakes wrapped and ready for your guests.'],
            ['key' => 'coastalkeepsakes', 'email' => 'coastalkeepsakes@osep.test', 'first' => 'Said', 'last' => 'Hamad',
             'business' => 'Coastal Keepsakes', 'tagline' => 'Island-made favours', 'location' => 'Zanzibar, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 13, 'jobs' => 50, 'response' => 4,
             'website' => 'https://coastalkeepsakes.example', 'instagram' => 'https://instagram.com/coastalkeepsakes',
             'description' => 'Handmade Zanzibari favours and keepsakes - spices, soaps and crafts - for destination weddings.'],
            ['key' => 'arushagiftstudio', 'email' => 'arushagiftstudio@osep.test', 'first' => 'Anna', 'last' => 'Kessy',
             'business' => 'Arusha Gift Studio', 'tagline' => 'Gifts & favours, Arusha', 'location' => 'Arusha, Tanzania',
             'years' => 4, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 12, 'jobs' => 44, 'response' => 4,
             'website' => 'https://arushagiftstudio.example', 'instagram' => 'https://instagram.com/arushagiftstudio',
             'description' => 'Wedding favours, hampers and corporate gifts for the Arusha and northern-circuit market.'],
        ];
    }
}
