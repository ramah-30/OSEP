<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian event-staffing agencies (marketplace Event Staffing category -
 * waiters, ushers, hostesses, bartenders). Named staffing agencies are sparse
 * online (planners usually coordinate staff), so these are representative
 * Tanzanian agency brands; demo contact + illustrative metrics.
 * See {@see RealVendorSeeder}.
 */
class EventStaffingVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'event-staffing';
    }

    protected function categoryFallbackName(): string
    {
        return 'Event Staffing';
    }

    protected function imagePool(): array
    {
        return [
            '1414235077428-338989a2e8c0', '1519671482749-fd09be7ccebf', '1555244162-803834f70033',
            '1530062845289-9109b2c9c868', '1467003909585-2f8a72700288', '1478145046317-39f10e56b5e9',
            '1523438885200-e635ba2c371e', '1490750967868-88aa4486c946',
        ];
    }

    protected function services(): array
    {
        return ['Waiters & servers', 'Ushers & protocol', 'Hostesses & greeters', 'Bartenders & baristas'];
    }

    protected function packages(): array
    {
        return [
            ['Core Crew', 600, 1500, 'per event', ['Up to 8 staff', 'Uniformed & briefed', '6-hour shift', 'On-site supervisor']],
            ['Full Service', 1800, 4000, 'per event', ['Up to 20 staff', 'Waiters, ushers & hostesses', 'Full-day cover', 'Team lead & coordination']],
            ['Premium Team', 4500, 9000, 'per event', ['Large trained team', 'Waiters, bartenders & protocol', 'Multi-day cover', 'Grooming & wardrobe standard', 'Dedicated event manager']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Additional staff (per head)', 'price' => 60_000], ['name' => 'Bartender', 'price' => 120_000], ['name' => 'Extra hours', 'price' => 90_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Service', 'Corporate Gala', 'Conference Ushering'];
    }

    protected function portfolioCaptions(): array
    {
        return ['On duty', 'Service in flow'];
    }

    protected function portfolioFeedback(): string
    {
        return 'Smart, warm and completely on it - the team made our guests feel looked after all night.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'karibustaff', 'email' => 'karibustaff@osep.test', 'first' => 'Grace', 'last' => 'Massawe',
             'business' => 'Karibu Event Staff', 'tagline' => 'Warm, professional event crew', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.75, 'reviews' => 30, 'jobs' => 220, 'response' => 2,
             'website' => 'https://karibustaff.example', 'instagram' => 'https://instagram.com/karibueventstaff',
             'description' => 'A trained, uniformed team of waiters, ushers and hostesses bringing warm, professional hospitality to weddings and events.'],
            ['key' => 'premierushers', 'email' => 'premierushers@osep.test', 'first' => 'Neema', 'last' => 'Laizer',
             'business' => 'Premier Ushers & Hostesses', 'tagline' => 'Polished ushers & hostesses', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 26, 'jobs' => 180, 'response' => 3,
             'website' => 'https://premierushers.example', 'instagram' => 'https://instagram.com/premierushers',
             'description' => 'Elegant, well-drilled ushers and hostesses for weddings, galas and corporate functions.'],
            ['key' => 'elitewaiters', 'email' => 'elitewaiters@osep.test', 'first' => 'Joseph', 'last' => 'Komba',
             'business' => 'Elite Waiters TZ', 'tagline' => 'Trained waiting staff', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 22, 'jobs' => 160, 'response' => 3,
             'website' => 'https://elitewaiters.example', 'instagram' => 'https://instagram.com/elitewaiterstz',
             'description' => 'Experienced, silver-service-trained waiting staff for plated dinners and large receptions.'],
            ['key' => 'serengeticrew', 'email' => 'serengeticrew@osep.test', 'first' => 'Emanuel', 'last' => 'Kaaya',
             'business' => 'Serengeti Event Crew', 'tagline' => 'Northern-circuit event staff', 'location' => 'Arusha, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 18, 'jobs' => 96, 'response' => 3,
             'website' => 'https://serengeticrew.example', 'instagram' => 'https://instagram.com/serengetieventcrew',
             'description' => 'Reliable waiting, ushering and hospitality crew for weddings and safaris across northern Tanzania.'],
            ['key' => 'coastalhospitality', 'email' => 'coastalhospitality@osep.test', 'first' => 'Said', 'last' => 'Khamis',
             'business' => 'Coastal Hospitality Staff', 'tagline' => 'Island event hospitality', 'location' => 'Zanzibar, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 15, 'jobs' => 72, 'response' => 4,
             'website' => 'https://coastalhospitality.example', 'instagram' => 'https://instagram.com/coastalhospitalitystaff',
             'description' => 'Friendly, English-and-Swahili speaking hospitality staff for destination weddings across Zanzibar.'],
            ['key' => 'zawadiushers', 'email' => 'zawadiushers@osep.test', 'first' => 'Zawadi', 'last' => 'Mnyika',
             'business' => 'Zawadi Ushers', 'tagline' => 'Ushers & protocol', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 14, 'jobs' => 80, 'response' => 3,
             'website' => 'https://zawadiushers.example', 'instagram' => 'https://instagram.com/zawadiushers',
             'description' => 'Smart ushers and protocol officers to seat, guide and look after your guests seamlessly.'],
            ['key' => 'proserve', 'email' => 'proserve@osep.test', 'first' => 'Peter', 'last' => 'Sanga',
             'business' => 'ProServe Event Staffing', 'tagline' => 'Full-service event teams', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 110, 'response' => 3,
             'website' => 'https://proserve.example', 'instagram' => 'https://instagram.com/proservetz',
             'description' => 'Full event staffing - waiters, bartenders, ushers and coordinators - supplied and managed end to end.'],
            ['key' => 'summithostess', 'email' => 'summithostess@osep.test', 'first' => 'Halima', 'last' => 'Ally',
             'business' => 'Summit Hostess Agency', 'tagline' => 'Corporate hostesses & greeters', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 13, 'jobs' => 64, 'response' => 3,
             'website' => 'https://summithostess.example', 'instagram' => 'https://instagram.com/summithostess',
             'description' => 'Professional hostesses and greeters for conferences, launches and corporate galas.'],
            ['key' => 'njemastaff', 'email' => 'njemastaff@osep.test', 'first' => 'Noel', 'last' => 'Charles',
             'business' => 'Njema Event Staff', 'tagline' => 'Dependable event staff, Mwanza', 'location' => 'Mwanza, Tanzania',
             'years' => 4, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 11, 'jobs' => 42, 'response' => 4,
             'website' => 'https://njemastaff.example', 'instagram' => 'https://instagram.com/njemaeventstaff',
             'description' => 'Dependable waiting and ushering staff for weddings and functions around Mwanza and the lake zone.'],
        ];
    }
}
