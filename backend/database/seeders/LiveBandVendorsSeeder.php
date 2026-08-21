<?php

namespace Database\Seeders;

/**
 * Nine real Tanzanian live bands & orchestras (marketplace Live Bands category).
 * Names/styles are real (Zanzibar taarab orchestras, Bongo & Afro-fusion bands);
 * demo contact + illustrative metrics. See {@see RealVendorSeeder}.
 */
class LiveBandVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'live-bands';
    }

    protected function categoryFallbackName(): string
    {
        return 'Live Bands';
    }

    protected function imagePool(): array
    {
        return [
            '1501386761578-eac5c94b800a', '1524650359799-842906ca1c06', '1470225620780-dba8ba36b745',
            '1459749411175-04bf5292ceea', '1514525253161-7a46d19cd819', '1429962714451-bb934ecdc4ec',
            '1493225457124-a3eb161ffa5f', '1470229538611-16ba8c7ffbd7', '1505236858219-8359eb29e329',
        ];
    }

    protected function services(): array
    {
        return ['Live band performance', 'Acoustic / cocktail sets', 'Ceremony music', 'Custom song requests'];
    }

    protected function packages(): array
    {
        return [
            ['Acoustic Set', 1500, 3000, 'per event', ['Up to 2 hours', '3-piece acoustic', 'PA included', '2 song requests']],
            ['Reception Band', 3500, 7000, 'per event', ['Up to 4 hours', 'Full band + vocalist', 'Full PA & lighting', 'First-dance rehearsal', '5 song requests']],
            ['Full Show', 8000, 16000, 'per event', ['Full-day / multi-set', 'Full band + horns', 'Concert PA & lighting', 'MC / hype segment', 'Custom arrangements']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra set (1 hour)', 'price' => 500_000], ['name' => 'Additional vocalist', 'price' => 400_000], ['name' => 'Saxophone soloist', 'price' => 600_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Reception', 'Cocktail Hour', 'Corporate Gala'];
    }

    protected function portfolioCaptions(): array
    {
        return ['On stage', 'Full dancefloor'];
    }

    protected function portfolioFeedback(): string
    {
        return 'The band read the room perfectly - everyone was up and dancing all night.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'mapanya', 'email' => 'mapanyaband@osep.test', 'first' => 'Juma', 'last' => 'Mapanya',
             'business' => 'Mapanya Band', 'tagline' => 'Afro-fusion, reggae & Bongo Flava', 'location' => 'Zanzibar, Tanzania',
             'years' => 10, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 38, 'jobs' => 120, 'response' => 3,
             'website' => 'https://mapanyaband.example', 'instagram' => 'https://instagram.com/mapanyaband',
             'description' => 'An Afro-fusion, hip-hop, reggae and Bongo-Flava band from Zanzibar, performing original songs that capture the island’s sound and spirit.'],
            ['key' => 'nadi', 'email' => 'nadiikhwansafaa@osep.test', 'first' => 'Rashid', 'last' => 'Hemed',
             'business' => 'Nadi Ikhwan Safaa', 'tagline' => 'Zanzibar’s historic taarab orchestra', 'location' => 'Stone Town, Zanzibar',
             'years' => 20, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.85, 'reviews' => 51, 'jobs' => 210, 'response' => 3,
             'website' => 'https://nadiikhwansafaa.example', 'instagram' => 'https://instagram.com/nadiikhwansafaa',
             'description' => 'One of Zanzibar’s most famous traditional taarab orchestras, bringing timeless Swahili wedding music to grand coastal celebrations.'],
            ['key' => 'culture', 'email' => 'culturemusicalclub@osep.test', 'first' => 'Ali', 'last' => 'Makame',
             'business' => 'Culture Musical Club', 'tagline' => 'Classic Zanzibari taarab', 'location' => 'Stone Town, Zanzibar',
             'years' => 18, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.8, 'reviews' => 44, 'jobs' => 180, 'response' => 3,
             'website' => 'https://culturemusicalclub.example', 'instagram' => 'https://instagram.com/culturemusicalclub',
             'description' => 'A renowned Zanzibar taarab orchestra performing classic Swahili repertoire for weddings and cultural celebrations.'],
            ['key' => 'yamoto', 'email' => 'yamotoband@osep.test', 'first' => 'Kassim', 'last' => 'Ally',
             'business' => 'Yamoto Band', 'tagline' => 'High-energy Bongo band', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 33, 'jobs' => 110, 'response' => 3,
             'website' => 'https://yamotoband.example', 'instagram' => 'https://instagram.com/yamotoband',
             'description' => 'A popular Dar es Salaam Bongo band delivering danceable hits and live energy for weddings and parties.'],
            ['key' => 'africanrev', 'email' => 'africanrevolution@osep.test', 'first' => 'Joseph', 'last' => 'Haule',
             'business' => 'African Revolution Band', 'tagline' => 'Afrobeat & live fusion', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 12, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 29, 'jobs' => 96, 'response' => 3,
             'website' => 'https://africanrevolutionband.example', 'instagram' => 'https://instagram.com/africanrevolutionband',
             'description' => 'A seasoned Dar es Salaam live act blending Afrobeat, rumba and fusion for receptions and corporate events.'],
            ['key' => 'fmacademia', 'email' => 'fmacademia@osep.test', 'first' => 'Frank', 'last' => 'Malaki',
             'business' => 'FM Academia', 'tagline' => 'Live jazz & Afro-jazz', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 15, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 31, 'jobs' => 130, 'response' => 3,
             'website' => 'https://fmacademia.example', 'instagram' => 'https://instagram.com/fmacademia',
             'description' => 'One of Tanzania’s respected live jazz ensembles, perfect for elegant cocktail hours and refined receptions.'],
            ['key' => 'shikamoo', 'email' => 'shikamoojazz@osep.test', 'first' => 'Hamisi', 'last' => 'Makassy',
             'business' => 'Shikamoo Jazz Band', 'tagline' => 'Classic Tanzanian dance band', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 20, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 27, 'jobs' => 150, 'response' => 4,
             'website' => 'https://shikamoojazz.example', 'instagram' => 'https://instagram.com/shikamoojazz',
             'description' => 'A veteran Tanzanian dance band reviving classic muziki wa dansi for nostalgic, joyful celebrations.'],
            ['key' => 'kilimanjaroband', 'email' => 'kilimanjaroband@osep.test', 'first' => 'Cosmas', 'last' => 'Chidumule',
             'business' => 'The Kilimanjaro Band', 'tagline' => 'WanaNjenje - Tanzanian classics', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 14, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 25, 'jobs' => 104, 'response' => 3,
             'website' => 'https://kilimanjaroband.example', 'instagram' => 'https://instagram.com/kilimanjaroband',
             'description' => 'The much-loved WanaNjenje - a Dar es Salaam institution performing beloved Tanzanian classics live.'],
            ['key' => 'bantugroup', 'email' => 'bantugroup@osep.test', 'first' => 'Peter', 'last' => 'Mwakyusa',
             'business' => 'Bantu Group', 'tagline' => 'Afro-fusion for any stage', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 18, 'jobs' => 62, 'response' => 4,
             'website' => 'https://bantugroup.example', 'instagram' => 'https://instagram.com/bantugroup',
             'description' => 'A versatile Dar es Salaam fusion band covering Afrobeats, soul and Bongo Flava for weddings and functions.'],
        ];
    }
}
