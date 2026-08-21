<?php

namespace Database\Seeders;

/**
 * Nine real Tanzanian cleaning companies (marketplace Cleaning Services category
 * - pre/post-event cleaning, venue restoration). Real firms found publicly
 * (SEARS, Eon, Monalisa, Broschem, WEinc, Kulaya Group) plus representative
 * regional cleaners; demo contact + illustrative metrics.
 * See {@see RealVendorSeeder}.
 */
class CleaningVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'cleaning-services';
    }

    protected function categoryFallbackName(): string
    {
        return 'Cleaning Services';
    }

    protected function imagePool(): array
    {
        return [
            '1581578731548-c64695cc6952', '1600880292203-757bb62b4baf', '1563453392212-326f5e854473',
            '1527515637462-cff94eecc1ac', '1585421514738-01798e348b17',
        ];
    }

    protected function services(): array
    {
        return ['Post-event cleaning', 'Pre-event deep clean', 'Restroom & waste management', 'Venue restoration'];
    }

    protected function packages(): array
    {
        return [
            ['Post-Event Clean', 400, 1200, 'per event', ['Up to 6 cleaners', 'Waste removal', 'Restroom servicing', 'Same-night turnaround']],
            ['Full Venue Service', 1500, 3500, 'per event', ['Pre + post cleaning', 'Up to 15 cleaners', 'Waste & recycling', 'Restroom attendants', 'Supervisor']],
            ['Complete Care', 4000, 8000, 'per event', ['Deep clean before & after', 'Large crew', 'Waste management & haulage', 'Floor & surface restoration', 'Overnight turnaround team']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra cleaner', 'price' => 50_000], ['name' => 'Restroom attendant', 'price' => 100_000], ['name' => 'Waste haulage truck', 'price' => 300_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Post-Wedding Cleanup', 'Conference Turnaround', 'Marquee Restoration'];
    }

    protected function portfolioCaptions(): array
    {
        return ['The crew', 'Spotless finish'];
    }

    protected function portfolioFeedback(): string
    {
        return 'They had the venue spotless by morning - fast, thorough and completely hassle-free.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'sears', 'email' => 'searscleaning@osep.test', 'first' => 'Samuel', 'last' => 'Mkwawa',
             'business' => 'SEARS Professional Cleaning', 'tagline' => 'Commercial & specialised cleaning', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.7, 'reviews' => 30, 'jobs' => 260, 'response' => 2,
             'website' => 'https://searscleaning.example', 'facebook' => 'https://www.facebook.com/searsindar',
             'description' => 'Commercial, residential and specialised cleaning in Dar es Salaam, including fast post-event venue turnarounds.'],
            ['key' => 'eon', 'email' => 'eoncleaners@osep.test', 'first' => 'Emanuel', 'last' => 'Haule',
             'business' => 'Eon Cleaners', 'tagline' => 'Professional domestic & office cleaning', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.65, 'reviews' => 26, 'jobs' => 200, 'response' => 3,
             'website' => 'https://cleaners.eon.co.tz', 'instagram' => 'https://instagram.com/eoncleaners',
             'description' => 'A registered Dar es Salaam cleaning company offering customised domestic, commercial and event cleaning.'],
            ['key' => 'monalisa', 'email' => 'monalisaclean@osep.test', 'first' => 'Monica', 'last' => 'Lyimo',
             'business' => 'Monalisa Commercial Cleaning', 'tagline' => 'Cleaning & landscaping', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 20, 'jobs' => 150, 'response' => 3,
             'website' => 'https://monalisaclean.example', 'instagram' => 'https://instagram.com/monalisacleaning',
             'description' => 'Kinondoni-based commercial cleaning and landscaping, keeping venues and grounds immaculate.'],
            ['key' => 'broschem', 'email' => 'broschem@osep.test', 'first' => 'Brian', 'last' => 'Sanga',
             'business' => 'Broschem', 'tagline' => 'One-stop specialised cleaning', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 10, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 22, 'jobs' => 180, 'response' => 3,
             'website' => 'https://services.broschem.co.tz', 'instagram' => 'https://instagram.com/broschem',
             'description' => 'A one-stop specialised cleaning company handling contract and emergency cleaning for commercial clients.'],
            ['key' => 'weinc', 'email' => 'weinccleaners@osep.test', 'first' => 'William', 'last' => 'Mnyika',
             'business' => 'WEinc Cleaners', 'tagline' => '#1 cleaning company, Dar', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 3, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 96, 'response' => 3,
             'website' => 'https://we-inc-cleaners.com', 'instagram' => 'https://instagram.com/weinccleaners',
             'description' => 'A fast-growing Dar es Salaam cleaning company (est. 2023) covering commercial, office and post-event cleaning.'],
            ['key' => 'kulaya', 'email' => 'kulayaclean@osep.test', 'first' => 'Kelvin', 'last' => 'Mtei',
             'business' => 'Kulaya Group Cleaning', 'tagline' => 'Office & post-construction cleaning', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 18, 'jobs' => 160, 'response' => 3,
             'website' => 'https://kulayagroup.com', 'instagram' => 'https://instagram.com/kulayagroup',
             'description' => 'Dar es Salaam cleaning services including office, post-construction and event venue cleaning.'],
            ['key' => 'sparkleclean', 'email' => 'sparkleclean@osep.test', 'first' => 'Sophia', 'last' => 'Massawe',
             'business' => 'Sparkle Clean TZ', 'tagline' => 'Event venue turnarounds', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 14, 'jobs' => 88, 'response' => 3,
             'website' => 'https://sparkleclean.example', 'instagram' => 'https://instagram.com/sparklecleantz',
             'description' => 'Specialist pre- and post-event cleaning with fast overnight venue turnarounds.'],
            ['key' => 'arushacleaning', 'email' => 'arushacleaning@osep.test', 'first' => 'Anna', 'last' => 'Kaaya',
             'business' => 'Arusha Cleaning Co', 'tagline' => 'Northern-circuit cleaning', 'location' => 'Arusha, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 13, 'jobs' => 70, 'response' => 3,
             'website' => 'https://arushacleaning.example', 'instagram' => 'https://instagram.com/arushacleaningco',
             'description' => 'Commercial and event cleaning for venues, lodges and conference centres across the northern circuit.'],
            ['key' => 'coastalcleaners', 'email' => 'coastalcleaners@osep.test', 'first' => 'Said', 'last' => 'Hamad',
             'business' => 'Coastal Cleaners Zanzibar', 'tagline' => 'Island venue cleaning', 'location' => 'Zanzibar, Tanzania',
             'years' => 5, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 11, 'jobs' => 50, 'response' => 4,
             'website' => 'https://coastalcleaners.example', 'instagram' => 'https://instagram.com/coastalcleaners',
             'description' => 'Post-event and venue cleaning for beach resorts and destination-wedding sites across Zanzibar.'],
        ];
    }
}
