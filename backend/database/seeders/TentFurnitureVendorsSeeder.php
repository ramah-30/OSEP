<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian tent & event-furniture rental companies (marketplace Tent &
 * Furniture Rental category). Real firms found publicly (REMIA, Techno Tents,
 * Tentickle, Mahraj, TARPO) plus representative regional hires; demo contact +
 * illustrative metrics. See {@see RealVendorSeeder}.
 */
class TentFurnitureVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'tent-furniture-rental';
    }

    protected function categoryFallbackName(): string
    {
        return 'Tent & Furniture Rental';
    }

    protected function imagePool(): array
    {
        return [
            '1533106418989-88406c7cc8ca', '1519167758481-83f550bb49b3', '1464366400600-7168b8af9bc3',
            '1478146059778-26028b07395a', '1414235077428-338989a2e8c0', '1523438885200-e635ba2c371e',
            '1490750967868-88aa4486c946', '1508610048659-a06b669e3321',
        ];
    }

    protected function services(): array
    {
        return ['Marquee & tent hire', 'Chairs & tables', 'Draping & flooring', 'Staging & dancefloor'];
    }

    protected function packages(): array
    {
        return [
            ['Garden Party', 1500, 3500, 'per event', ['Frame tent (up to 100)', 'Plastic / Tiffany chairs', 'Round tables', 'Basic lighting', 'Setup & teardown']],
            ['Grand Marquee', 4000, 9000, 'per event', ['Large marquee (up to 300)', 'Chiavari chairs', 'Linened tables', 'Draping & flooring', 'Stage & dancefloor']],
            ['Luxury Event', 10000, 22000, 'per event', ['Custom marquee build', 'Premium furniture', 'Full draping, flooring & HVAC', 'Stage, dancefloor & lounge', 'Dedicated setup crew']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra 50 chairs', 'price' => 250_000], ['name' => 'Dancefloor section', 'price' => 400_000], ['name' => 'Cooling / HVAC unit', 'price' => 700_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Garden Wedding', 'Corporate Tent', 'Beach Marquee'];
    }

    protected function portfolioCaptions(): array
    {
        return ['Tent & seating', 'Dressed & ready'];
    }

    protected function portfolioFeedback(): string
    {
        return 'The marquee and furniture were spotless and the setup crew were fast and professional.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'remia', 'email' => 'remiarental@osep.test', 'first' => 'Remigius', 'last' => 'Mushi',
             'business' => 'REMIA Rentals', 'tagline' => 'Tents, tables & chairs', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 40, 'jobs' => 240, 'response' => 2,
             'website' => 'https://remiarental.com', 'instagram' => 'https://instagram.com/remiarental',
             'description' => 'Reliable, affordable tent, table and chair rentals for weddings, corporate events and celebrations in Dar es Salaam.'],
            ['key' => 'technotents', 'email' => 'technotents@osep.test', 'first' => 'Tariq', 'last' => 'Hassan',
             'business' => 'Techno Tents', 'tagline' => 'World-class tents & marquees', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 12, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.75, 'reviews' => 46, 'jobs' => 300, 'response' => 2,
             'website' => 'https://www.technotents.com', 'instagram' => 'https://instagram.com/technotents',
             'description' => 'Supplies aluminium, canvas, frame and pagoda tents plus Tiffany chairs and round tables for functions and weddings.'],
            ['key' => 'tentickle', 'email' => 'tentickletz@osep.test', 'first' => 'Tim', 'last' => 'Nkya',
             'business' => 'Tentickle Tanzania', 'tagline' => 'Stretch tents for any terrain', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 28, 'jobs' => 150, 'response' => 3,
             'website' => 'https://tentickletanzania.com', 'instagram' => 'https://instagram.com/tentickletanzania',
             'description' => 'Signature stretch tents that can be set up on almost any terrain, ideal for gardens and beach events.'],
            ['key' => 'mahraj', 'email' => 'mahrajindustries@osep.test', 'first' => 'Mahesh', 'last' => 'Raj',
             'business' => 'Mahraj Industries', 'tagline' => 'Tents, furniture, staging & flooring', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 14, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 31, 'jobs' => 260, 'response' => 3,
             'website' => 'https://mahrajindustries.com', 'instagram' => 'https://instagram.com/mahrajindustries',
             'description' => 'Full event fit-out - tents with lining, draping, flooring, HVAC, chiavari/tiffany chairs, staging and dancefloors.'],
            ['key' => 'tarpo', 'email' => 'tarpotz@osep.test', 'first' => 'Paul', 'last' => 'Tarimo',
             'business' => 'TARPO', 'tagline' => 'Event tents & décor for hire', 'location' => 'Arusha, Tanzania',
             'years' => 11, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 24, 'jobs' => 170, 'response' => 3,
             'website' => 'https://sectors.tarpo.com', 'instagram' => 'https://instagram.com/tarpotanzania',
             'description' => 'Arusha-based supplier of event and function tents, chairs and décor for hire across northern Tanzania.'],
            ['key' => 'coastalmarquees', 'email' => 'coastalmarquees@osep.test', 'first' => 'Charles', 'last' => 'Mnyika',
             'business' => 'Coastal Marquees', 'tagline' => 'Beach & garden marquees', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 18, 'jobs' => 96, 'response' => 3,
             'website' => 'https://coastalmarquees.example', 'instagram' => 'https://instagram.com/coastalmarquees',
             'description' => 'Elegant beach and garden marquees with full furniture and draping for coastal celebrations.'],
            ['key' => 'serengetirentals', 'email' => 'serengetirentals@osep.test', 'first' => 'Emanuel', 'last' => 'Kaaya',
             'business' => 'Serengeti Event Rentals', 'tagline' => 'Tents & furniture, northern circuit', 'location' => 'Arusha, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 17, 'jobs' => 88, 'response' => 3,
             'website' => 'https://serengetirentals.example', 'instagram' => 'https://instagram.com/serengetieventrentals',
             'description' => 'Tents, chairs, tables and staging for weddings and corporate events across the northern circuit.'],
            ['key' => 'zanzibartents', 'email' => 'zanzibartents@osep.test', 'first' => 'Juma', 'last' => 'Salum',
             'business' => 'Zanzibar Tents & Chairs', 'tagline' => 'Island event hire', 'location' => 'Zanzibar, Tanzania',
             'years' => 5, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 14, 'jobs' => 60, 'response' => 4,
             'website' => 'https://zanzibartents.example', 'instagram' => 'https://instagram.com/zanzibartentschairs',
             'description' => 'Marquees, chairs and tables for destination weddings and events across Zanzibar.'],
            ['key' => 'kilipartyhire', 'email' => 'kilipartyhire@osep.test', 'first' => 'Godfrey', 'last' => 'Mtei',
             'business' => 'Kilimanjaro Party Hire', 'tagline' => 'Tents & furniture, Moshi', 'location' => 'Moshi, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 78, 'response' => 3,
             'website' => 'https://kilipartyhire.example', 'instagram' => 'https://instagram.com/kilimanjaropartyhire',
             'description' => 'Tent, furniture and staging hire for weddings and functions around Moshi and Kilimanjaro.'],
        ];
    }
}
