<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian entertainment acts (marketplace Entertainment category -
 * dancers, acrobats, photo booths, fireworks; distinct from DJs / live bands).
 * Real acts/concepts (Tanzanian acrobats, Mdundiko/Ngoma dancers) plus
 * representative brands; demo contact + illustrative metrics.
 * See {@see RealVendorSeeder}.
 */
class EntertainmentVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'entertainment';
    }

    protected function categoryFallbackName(): string
    {
        return 'Entertainment';
    }

    protected function imagePool(): array
    {
        return [
            '1470225620780-dba8ba36b745', '1429962714451-bb934ecdc4ec', '1514525253161-7a46d19cd819',
            '1533174072545-7a4b6ad7a6c3', '1493225457124-a3eb161ffa5f', '1459749411175-04bf5292ceea',
            '1467810563316-b5476525c0f9', '1498931299472-f7a63a5a1cfa', '1505236858219-8359eb29e329',
        ];
    }

    protected function services(): array
    {
        return ['Cultural dancers & drummers', 'Acrobats & performers', 'Photo booth hire', 'Fireworks & special effects'];
    }

    protected function packages(): array
    {
        return [
            ['Feature Act', 800, 2500, 'per event', ['One performance set', 'Costumes & props', 'Up to 4 performers', 'Sound if needed']],
            ['Showcase', 3000, 6000, 'per event', ['Multiple sets', 'Dancers + acrobats', 'MC coordination', 'Photo booth (3 hrs)']],
            ['Grand Spectacle', 7000, 16000, 'per event', ['Full entertainment programme', 'Dancers, acrobats & fire show', 'Fireworks finale', 'Photo booth all night', 'Dedicated stage manager']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra performer', 'price' => 200_000], ['name' => 'Fireworks upgrade', 'price' => 800_000], ['name' => 'Photo booth props pack', 'price' => 150_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Entrance', 'Corporate Showcase', 'New Year Spectacle'];
    }

    protected function portfolioCaptions(): array
    {
        return ['The performance', 'Crowd reaction'];
    }

    protected function portfolioFeedback(): string
    {
        return 'They brought the house down - the energy was electric and our guests will never forget it.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'tzacrobats', 'email' => 'tzacrobats@osep.test', 'first' => 'Juma', 'last' => 'Athumani',
             'business' => 'Tanzania African Acrobats', 'tagline' => 'Jaw-dropping acrobatic shows', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 10, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.8, 'reviews' => 36, 'jobs' => 150, 'response' => 3,
             'website' => 'https://tzacrobats.example', 'instagram' => 'https://instagram.com/tzafricanacrobats',
             'description' => 'A Dar es Salaam acrobatic troupe of up to eight, delivering everything from street-style shows to large corporate stage spectacles.'],
            ['key' => 'mdundiko', 'email' => 'mdundikodancers@osep.test', 'first' => 'Salma', 'last' => 'Rajabu',
             'business' => 'Mdundiko Dancers', 'tagline' => 'Traditional Zaramo dance', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 12, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.75, 'reviews' => 30, 'jobs' => 170, 'response' => 3,
             'website' => 'https://mdundikodancers.example', 'instagram' => 'https://instagram.com/mdundikodancers',
             'description' => 'Traditional Mdundiko dancers and drummers of the Zaramo people, perfect for send-offs, entrances and cultural celebrations.'],
            ['key' => 'snapshotbooth', 'email' => 'snapshotbooth@osep.test', 'first' => 'Neema', 'last' => 'Mushi',
             'business' => 'Snapshot Photo Booth TZ', 'tagline' => 'Fun photo booths & props', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 22, 'jobs' => 130, 'response' => 3,
             'website' => 'https://snapshotbooth.example', 'instagram' => 'https://instagram.com/snapshotboothtz',
             'description' => 'Photo booth hire with fun props, instant prints and digital sharing for weddings and parties.'],
            ['key' => 'sparklefireworks', 'email' => 'sparklefireworks@osep.test', 'first' => 'Emanuel', 'last' => 'Haule',
             'business' => 'Sparkle Fireworks Tanzania', 'tagline' => 'Fireworks & pyro shows', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 18, 'jobs' => 90, 'response' => 3,
             'website' => 'https://sparklefireworks.example', 'instagram' => 'https://instagram.com/sparklefireworkstz',
             'description' => 'Licensed fireworks and pyrotechnic displays for weddings, New Year and grand finales.'],
            ['key' => 'ngomafire', 'email' => 'ngomafire@osep.test', 'first' => 'Hassan', 'last' => 'Khamis',
             'business' => 'Ngoma Fire Dancers', 'tagline' => 'Fire & drum performances', 'location' => 'Zanzibar, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 16, 'jobs' => 80, 'response' => 3,
             'website' => 'https://ngomafire.example', 'instagram' => 'https://instagram.com/ngomafiredancers',
             'description' => 'Coastal fire dancers and drummers bringing dramatic beach-side performances to Zanzibar weddings.'],
            ['key' => 'cirquebongo', 'email' => 'cirquebongo@osep.test', 'first' => 'Daniel', 'last' => 'Mnyika',
             'business' => 'Cirque Bongo', 'tagline' => 'Circus & aerial acts', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 15, 'jobs' => 66, 'response' => 3,
             'website' => 'https://cirquebongo.example', 'instagram' => 'https://instagram.com/cirquebongo',
             'description' => 'Contemporary circus, aerial and stilt performers for a show-stopping event centrepiece.'],
            ['key' => 'magicmoments', 'email' => 'magicmoments@osep.test', 'first' => 'Peter', 'last' => 'Gervas',
             'business' => 'Magic Moments TZ', 'tagline' => 'Magicians & roaming acts', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 14, 'jobs' => 70, 'response' => 3,
             'website' => 'https://magicmoments.example', 'instagram' => 'https://instagram.com/magicmomentstz',
             'description' => 'Close-up magic, illusions and roaming entertainment to delight guests during receptions and cocktail hours.'],
            ['key' => 'kaributroupe', 'email' => 'kaributroupe@osep.test', 'first' => 'Grace', 'last' => 'Kaaya',
             'business' => 'Karibu Cultural Troupe', 'tagline' => 'Cultural dance & song', 'location' => 'Arusha, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 16, 'jobs' => 88, 'response' => 3,
             'website' => 'https://kaributroupe.example', 'instagram' => 'https://instagram.com/karibuculturaltroupe',
             'description' => 'Northern-circuit cultural dancers and singers showcasing Maasai and Chagga traditions at weddings and events.'],
            ['key' => 'stiltsparkle', 'email' => 'stiltsparkle@osep.test', 'first' => 'Frank', 'last' => 'Lweno',
             'business' => 'Stilt & Sparkle Entertainers', 'tagline' => 'Stilt walkers & roaming glamour', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 4, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 12, 'jobs' => 48, 'response' => 4,
             'website' => 'https://stiltsparkle.example', 'instagram' => 'https://instagram.com/stiltsparkle',
             'description' => 'Stilt walkers, LED performers and roaming glamour acts for grand entrances and cocktail receptions.'],
        ];
    }
}
