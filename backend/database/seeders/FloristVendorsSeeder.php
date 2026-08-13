<?php

namespace Database\Seeders;

/**
 * Nine real Tanzanian florists (marketplace Florists category — distinct from
 * Decorators). Real shops found publicly + a few representative regional
 * florists; demo contact + illustrative metrics. See {@see RealVendorSeeder}.
 */
class FloristVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'florists';
    }

    protected function categoryFallbackName(): string
    {
        return 'Florists';
    }

    protected function imagePool(): array
    {
        return [
            '1465495976277-4387d4b0b4c6', '1478146059778-26028b07395a', '1490750967868-88aa4486c946',
            '1487530811176-3780de880c2d', '1533616688419-b7a585564566', '1478476868527-002ae3f3e159',
            '1464047736614-af63643285bf', '1519167758481-83f550bb49b3',
        ];
    }

    protected function services(): array
    {
        return ['Bridal bouquets', 'Ceremony & aisle florals', 'Centrepieces & table flowers', 'Fresh flower delivery'];
    }

    protected function packages(): array
    {
        return [
            ['Bridal Flowers', 400, 1200, 'per event', ['Bridal bouquet', '2 bridesmaid posies', '2 buttonholes', 'Delivery']],
            ['Ceremony & Reception', 1500, 4000, 'per event', ['Bridal party flowers', 'Aisle & arch florals', '8 centrepieces', 'Top-table arrangement']],
            ['Full Floral Design', 5000, 12000, 'per event', ['Complete floral styling', 'Premium seasonal blooms', 'Arch, aisle & backdrop', 'Centrepieces throughout', 'On-site florist team']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Flower crown', 'price' => 120_000], ['name' => 'Extra centrepiece', 'price' => 90_000], ['name' => 'Car florals', 'price' => 200_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Bridal Bouquet', 'Ceremony Florals', 'Reception Centrepieces'];
    }

    protected function portfolioCaptions(): array
    {
        return ['The blooms', 'Fresh detail'];
    }

    protected function portfolioFeedback(): string
    {
        return 'The flowers were fresh, abundant and exactly the palette we wanted — absolutely gorgeous.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'thefloralco', 'email' => 'thefloralco@osep.test', 'first' => 'Farida', 'last' => 'Noor',
             'business' => 'The Floral Co', 'tagline' => 'Premium hand-crafted bouquets', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.8, 'reviews' => 36, 'jobs' => 130, 'response' => 2,
             'website' => 'https://thefloral.co.tz', 'instagram' => 'https://instagram.com/thefloralco.tz',
             'description' => 'Premium hand-crafted bouquets and wedding florals in Dar es Salaam, using fresh premium blooms with same-day delivery.'],
            ['key' => 'theflowerbar', 'email' => 'theflowerbar@osep.test', 'first' => 'Grace', 'last' => 'Massawe',
             'business' => 'The Flower Bar', 'tagline' => 'Your one-stop flower shop', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 33, 'jobs' => 140, 'response' => 3,
             'website' => 'https://theflowerbartz.com', 'instagram' => 'https://instagram.com/theflowerbartz',
             'description' => 'A Dar es Salaam flower shop crafting bridal bouquets to perfection, with reliable delivery of flowers, décor and indoor plants.'],
            ['key' => 'cyberflorist', 'email' => 'cyberflorist@osep.test', 'first' => 'Neema', 'last' => 'Kimaro',
             'business' => 'Cyber Florist Tanzania', 'tagline' => 'Nationwide flower delivery', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 28, 'jobs' => 160, 'response' => 3,
             'website' => 'https://tanzania.cyber-florist.com', 'instagram' => 'https://instagram.com/cyberfloristtz',
             'description' => 'Flower and gift delivery throughout Tanzania, from Dar es Salaam to Arusha and beyond, for weddings and every occasion.'],
            ['key' => 'italianflora', 'email' => 'italianflora@osep.test', 'first' => 'Amina', 'last' => 'Said',
             'business' => 'Italian Flora Tanzania', 'tagline' => 'Roses, lilies & orchids', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 22, 'jobs' => 96, 'response' => 3,
             'website' => 'https://italianflora.example', 'instagram' => 'https://instagram.com/italianfloratz',
             'description' => 'Stunning arrangements of roses, lilies, orchids and seasonal blooms, delivered across Dar es Salaam and beyond.'],
            ['key' => 'flowers4tz', 'email' => 'flowers4tz@osep.test', 'first' => 'Rehema', 'last' => 'Mushi',
             'business' => 'Flowers4Tanzania', 'tagline' => 'Fresh flowers, delivered', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 20, 'jobs' => 110, 'response' => 3,
             'website' => 'https://www.flowers4tanzania.com', 'instagram' => 'https://instagram.com/flowers4tanzania',
             'description' => 'Florist delivering fresh bouquets and wedding arrangements across Tanzania for weddings, anniversaries and events.'],
            ['key' => 'bloomandpetal', 'email' => 'bloomandpetal@osep.test', 'first' => 'Beatrice', 'last' => 'Laizer',
             'business' => 'Bloom & Petal TZ', 'tagline' => 'Modern wedding florals', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 17, 'jobs' => 72, 'response' => 3,
             'website' => 'https://bloomandpetal.example', 'instagram' => 'https://instagram.com/bloomandpetaltz',
             'description' => 'Contemporary wedding florals and installations with a fresh, romantic signature style.'],
            ['key' => 'roseandlily', 'email' => 'roseandlily@osep.test', 'first' => 'Lilian', 'last' => 'Mnyika',
             'business' => 'Rose & Lily Florists', 'tagline' => 'Classic bridal blooms', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 80, 'response' => 3,
             'website' => 'https://roseandlily.example', 'instagram' => 'https://instagram.com/roseandlilytz',
             'description' => 'Classic, elegant bridal bouquets and ceremony flowers for timeless celebrations.'],
            ['key' => 'arushablooms', 'email' => 'arushablooms@osep.test', 'first' => 'Neema', 'last' => 'Kaaya',
             'business' => 'Arusha Blooms', 'tagline' => 'Northern-circuit florals', 'location' => 'Arusha, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 15, 'jobs' => 64, 'response' => 3,
             'website' => 'https://arushablooms.example', 'instagram' => 'https://instagram.com/arushablooms',
             'description' => 'Fresh wedding and event florals for Arusha, Moshi and the northern safari circuit.'],
            ['key' => 'zanzibarpetals', 'email' => 'zanzibarpetals@osep.test', 'first' => 'Zainab', 'last' => 'Khamis',
             'business' => 'Zanzibar Petals', 'tagline' => 'Island wedding flowers', 'location' => 'Zanzibar, Tanzania',
             'years' => 5, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 13, 'jobs' => 52, 'response' => 4,
             'website' => 'https://zanzibarpetals.example', 'instagram' => 'https://instagram.com/zanzibarpetals',
             'description' => 'Tropical bridal bouquets and beach-wedding florals for destination celebrations across Zanzibar.'],
        ];
    }
}
