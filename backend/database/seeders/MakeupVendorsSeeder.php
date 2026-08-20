<?php

namespace Database\Seeders;

/**
 * Nine real Tanzanian bridal makeup artists (marketplace Makeup Artists
 * category). Names/styles sourced from public listings (Jana Tribe, Bridestory);
 * demo contact + illustrative metrics. See {@see RealVendorSeeder}.
 */
class MakeupVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'makeup-artists';
    }

    protected function categoryFallbackName(): string
    {
        return 'Makeup Artists';
    }

    protected function imagePool(): array
    {
        return [
            '1487412720507-e7ab37603c6f', '1522337660859-02fbefca4702', '1516975080664-ed2fc6a32937',
            '1503236823255-94609f598e71', '1596462502278-27bfdc403348', '1512496015851-a90fb38ba796',
            '1560066984-138dadb4c035',
        ];
    }

    protected function services(): array
    {
        return ['Bridal makeup', 'Bridal party makeup', 'Hair styling', 'Trial session'];
    }

    protected function packages(): array
    {
        return [
            ['Bridal Trial', 150, 350, 'per session', ['Consultation', 'Full trial look', 'Product & shade matching']],
            ['Bridal Day', 400, 900, 'per event', ['Bridal makeup + hair', 'Lashes included', 'Touch-up kit', 'On-location service']],
            ['Bridal Party', 900, 2500, 'per event', ['Bride + up to 4', 'Hair & makeup for all', 'Two artists', 'All-day touch-ups']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra face (makeup)', 'price' => 120_000], ['name' => 'Hair styling add-on', 'price' => 100_000], ['name' => 'Early call-time', 'price' => 150_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Bridal Glam', 'Traditional Bride', 'Editorial Shoot'];
    }

    protected function portfolioCaptions(): array
    {
        return ['The look', 'Final touches'];
    }

    protected function portfolioFeedback(): string
    {
        return 'I felt like the best version of myself — flawless makeup that lasted the whole day.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'lush', 'email' => 'lushmakeup@osep.test', 'first' => 'Lucy', 'last' => 'Mushi',
             'business' => 'Lush Makeup', 'tagline' => 'Radiant bridal glam, will travel', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.8, 'reviews' => 49, 'jobs' => 160, 'response' => 2,
             'website' => 'https://lushmakeup.example', 'instagram' => 'https://instagram.com/lushmakeup',
             'description' => 'A top-rated Dar es Salaam bridal artist delivering radiant, long-wearing glam, travelling across Tanzania for destination weddings.'],
            ['key' => 'nasimbarde', 'email' => 'nasimbarde@osep.test', 'first' => 'Nasim', 'last' => 'Barde',
             'business' => 'Nasim Barde Hair & Makeup Studio', 'tagline' => 'Hair & makeup studio, Dar es Salaam', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 10, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.75, 'reviews' => 41, 'jobs' => 140, 'response' => 3,
             'website' => 'https://nasimbarde.example', 'instagram' => 'https://instagram.com/nasim.barde',
             'description' => 'A Dar es Salaam hair-and-makeup studio known for polished bridal looks and full glam for the whole party.'],
            ['key' => 'makeupbyjojo', 'email' => 'makeupbyjojo@osep.test', 'first' => 'Joan', 'last' => 'Kimaro',
             'business' => 'Makeup by Jojo', 'tagline' => 'Soft, radiant bridal & brows', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 30, 'jobs' => 88, 'response' => 3,
             'website' => 'https://makeupbyjojo.example', 'instagram' => 'https://instagram.com/makeupbyjojotz',
             'description' => 'Bridal and brow artist delivering soft, radiant glam with a natural, flattering finish.'],
            ['key' => 'rosekayuga', 'email' => 'rosekayuga@osep.test', 'first' => 'Rose', 'last' => 'Kayuga',
             'business' => 'Rose Kayuga Makeup', 'tagline' => 'Elegant bridal artistry', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 28, 'jobs' => 82, 'response' => 3,
             'website' => 'https://rosekayuga.example', 'instagram' => 'https://instagram.com/rosekayuga',
             'description' => 'A sought-after Tanzanian bridal makeup artist known for elegant, camera-ready looks.'],
            ['key' => 'lavie', 'email' => 'laviemakeup@osep.test', 'first' => 'Lavender', 'last' => 'Ismail',
             'business' => 'Lavie Makeup', 'tagline' => 'Modern glam & glow', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 22, 'jobs' => 64, 'response' => 4,
             'website' => 'https://laviemakeup.example', 'instagram' => 'https://instagram.com/laviemakeup',
             'description' => 'Contemporary bridal and event makeup with a fresh, glowing signature finish.'],
            ['key' => 'zuu', 'email' => 'zuumakeup@osep.test', 'first' => 'Zuena', 'last' => 'Ally',
             'business' => 'Zuu Makeup Studio', 'tagline' => 'Bridal & event studio', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 26, 'jobs' => 76, 'response' => 3,
             'website' => 'https://zuumakeup.example', 'instagram' => 'https://instagram.com/zuumakeupstudio',
             'description' => 'A Dar es Salaam studio offering bridal and event makeup with a warm, personalised approach.'],
            ['key' => 'cherrysuzie', 'email' => 'cherrysuzie@osep.test', 'first' => 'Suzan', 'last' => 'Cherry',
             'business' => 'CherrySuzie', 'tagline' => 'Bridal hair & makeup, Arusha', 'location' => 'Arusha, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 24, 'jobs' => 70, 'response' => 3,
             'website' => 'https://cherrysuzie.example', 'instagram' => 'https://instagram.com/cherrysuzie',
             'description' => 'Personalised bridal looks with both hair and makeup, based near Mount Meru and serving the northern circuit.'],
            ['key' => 'blushme', 'email' => 'blushmebeauty@osep.test', 'first' => 'Beatrice', 'last' => 'Mniko',
             'business' => 'Blushme Beauty', 'tagline' => 'Flawless bridal beauty', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 19, 'jobs' => 54, 'response' => 4,
             'website' => 'https://blushmebeauty.example', 'instagram' => 'https://instagram.com/blushmebeauty',
             'description' => 'Bridal and occasion makeup focused on flawless, skin-like finishes that photograph beautifully.'],
            ['key' => 'emeraldglows', 'email' => 'emeraldglows@osep.test', 'first' => 'Emmerald', 'last' => 'John',
             'business' => 'Emerald Glows', 'tagline' => 'Glow-up bridal artistry', 'location' => 'Mwanza, Tanzania',
             'years' => 4, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 16, 'jobs' => 40, 'response' => 4,
             'website' => 'https://emeraldglows.example', 'instagram' => 'https://instagram.com/emeraldglows',
             'description' => 'Mwanza-based bridal artist creating radiant, glowing looks for weddings and special occasions.'],
        ];
    }
}
