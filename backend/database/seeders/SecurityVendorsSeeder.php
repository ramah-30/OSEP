<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian event-security companies (marketplace Security Services
 * category). Real firms found publicly (GardaWorld, TL, Blackrock, Chaka Force,
 * Kiduli, Eager, Vital Force) plus two representative regional firms; demo
 * contact + illustrative metrics. See {@see RealVendorSeeder}.
 */
class SecurityVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'security-services';
    }

    protected function categoryFallbackName(): string
    {
        return 'Security Services';
    }

    protected function imagePool(): array
    {
        return [
            '1454117096348-e4abbeba002c', '1557597774-9d273605dfa9', '1590650516494-0c8e4a4dd67e',
            '1436450412740-6b988f486c6b', '1520869562399-e772f042f422', '1589578527966-fdac0f44566c',
        ];
    }

    protected function services(): array
    {
        return ['Event guards', 'Crowd management', 'VIP / executive protection', 'Access control'];
    }

    protected function packages(): array
    {
        return [
            ['Standard Cover', 800, 2000, 'per event', ['Up to 6 guards', 'Entry & access control', '6-hour cover', 'On-site supervisor']],
            ['Full Event', 2500, 5000, 'per event', ['Up to 15 guards', 'Crowd & queue management', 'Full-day cover', 'Radios & metal detectors']],
            ['VIP & Executive', 5500, 12000, 'per event', ['Close-protection team', 'Advance site sweep', 'Vehicle escort', 'Control-room coordination']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Additional guard', 'price' => 90_000], ['name' => 'Metal detector unit', 'price' => 150_000], ['name' => 'CCTV / control room', 'price' => 500_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Security', 'Corporate Conference', 'VIP Gala'];
    }

    protected function portfolioCaptions(): array
    {
        return ['Access control', 'On duty'];
    }

    protected function portfolioFeedback(): string
    {
        return 'Professional, discreet and reassuring — our guests felt safe and the day ran without a hitch.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'gardaworld', 'email' => 'gardaworldtz@osep.test', 'first' => 'George', 'last' => 'Wanjala',
             'business' => 'GardaWorld Security', 'tagline' => 'Nationwide security services', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 15, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.7, 'reviews' => 44, 'jobs' => 380, 'response' => 2,
             'website' => 'https://www.garda.com', 'instagram' => 'https://instagram.com/gardaworld',
             'description' => 'A leading security provider delivering manned guarding and event security across Tanzania from its Dar es Salaam base.'],
            ['key' => 'tlsecurity', 'email' => 'tlsecurity@osep.test', 'first' => 'Tumaini', 'last' => 'Lyimo',
             'business' => 'TL Security', 'tagline' => 'Manned guards & event security', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 12, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 33, 'jobs' => 240, 'response' => 2,
             'website' => 'https://tlsecurityco.com', 'instagram' => 'https://instagram.com/tlsecurityco',
             'description' => 'Premium Tanzanian security firm specialising in manned guards, event security, executive protection and patrols.'],
            ['key' => 'blackrock', 'email' => 'blackrocktz@osep.test', 'first' => 'Bakari', 'last' => 'Ismail',
             'business' => 'Blackrock Security Tanzania', 'tagline' => 'Event security & crowd management', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 10, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 28, 'jobs' => 160, 'response' => 3,
             'website' => 'https://blackrock.co.tz', 'instagram' => 'https://instagram.com/blackrocksecuritytz',
             'description' => 'Elite protection services with strong crowd-management skills for events across Tanzania.'],
            ['key' => 'chakaforce', 'email' => 'chakaforce@osep.test', 'first' => 'Chacha', 'last' => 'Marwa',
             'business' => 'Chaka Force Security Services', 'tagline' => '15+ years guarding events & VIPs', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 15, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 26, 'jobs' => 220, 'response' => 3,
             'website' => 'https://chakaforce.example', 'instagram' => 'https://instagram.com/chakaforcesecurity',
             'description' => 'Over 15 years providing guards for events, executives, VIPs and private parties across Tanzania.'],
            ['key' => 'kiduli', 'email' => 'kidulisecurity@osep.test', 'first' => 'Daniel', 'last' => 'Kiduli',
             'business' => 'Kiduli Security', 'tagline' => 'Trained, disciplined guarding', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 22, 'jobs' => 140, 'response' => 3,
             'website' => 'https://www.kidulisecurity.co.tz', 'instagram' => 'https://instagram.com/kidulisecurity',
             'description' => 'More than 100 trained guards and supervisors nationwide, observing Tanzania Police Force standards of conduct.'],
            ['key' => 'eager', 'email' => 'eagersecurity@osep.test', 'first' => 'Erick', 'last' => 'Mwita',
             'business' => 'Eager Security', 'tagline' => 'Private events & business security', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 20, 'jobs' => 120, 'response' => 3,
             'website' => 'https://www.eagersecurity.co.tz', 'instagram' => 'https://instagram.com/eagersecurity',
             'description' => 'Sets the benchmark for Tanzanian security, handling both private events and ongoing business security.'],
            ['key' => 'vitalforce', 'email' => 'vitalforce@osep.test', 'first' => 'Victor', 'last' => 'Massawe',
             'business' => 'Vital Force Security', 'tagline' => 'Dependable event guarding', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 17, 'jobs' => 96, 'response' => 4,
             'website' => 'https://vitalforce.example', 'instagram' => 'https://instagram.com/vitalforcesecurity',
             'description' => 'A Dar es Salaam security company providing dependable guarding for events and premises.'],
            ['key' => 'northernshield', 'email' => 'northernshield@osep.test', 'first' => 'Noel', 'last' => 'Laizer',
             'business' => 'Northern Shield Security', 'tagline' => 'Event security, northern circuit', 'location' => 'Arusha, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 70, 'response' => 3,
             'website' => 'https://northernshield.example', 'instagram' => 'https://instagram.com/northernshieldsecurity',
             'description' => 'Event and venue security for weddings, safaris and corporate functions across northern Tanzania.'],
            ['key' => 'coastalguard', 'email' => 'coastalguard@osep.test', 'first' => 'Said', 'last' => 'Khamis',
             'business' => 'Coastal Guard Services', 'tagline' => 'Island event security', 'location' => 'Zanzibar, Tanzania',
             'years' => 6, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 14, 'jobs' => 58, 'response' => 4,
             'website' => 'https://coastalguard.example', 'instagram' => 'https://instagram.com/coastalguardservices',
             'description' => 'Licensed event security and crowd control for destination weddings and functions across Zanzibar.'],
        ];
    }
}
