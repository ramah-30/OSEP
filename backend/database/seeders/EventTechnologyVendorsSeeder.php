<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian event-technology firms (marketplace Event Technology category —
 * ticketing, registration, check-in, live streaming). Real firms found publicly
 * (EventSquare, 19 Events, Hosanna Higher Technologies, Tukiio) plus
 * representative brands; demo contact + illustrative metrics.
 * See {@see RealVendorSeeder}.
 */
class EventTechnologyVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'event-technology';
    }

    protected function categoryFallbackName(): string
    {
        return 'Event Technology';
    }

    protected function imagePool(): array
    {
        return [
            '1517245386807-bb43f82c33c4', '1460925895917-afdab827c52f', '1498050108023-c5249f4df085',
            '1520170350707-b2da59970118', '1545454675-3531b543be5d', '1516873240891-4bf014598ab4',
            '1459749411175-04bf5292ceea', '1587825140708-dfaf72ae4b04', '1511578314322-379afb476865',
        ];
    }

    protected function services(): array
    {
        return ['E-ticketing & registration', 'QR check-in & access', 'Live streaming', 'RSVP & guest management'];
    }

    protected function packages(): array
    {
        return [
            ['Ticketing & Check-in', 800, 2000, 'per event', ['Online ticketing page', 'QR e-tickets', 'On-site scan check-in', '1 attendant']],
            ['Registration Suite', 2500, 5000, 'per event', ['Custom registration', 'Badge printing', 'Access control', '2–3 attendants', 'Real-time dashboard']],
            ['Full Event Tech', 6000, 14000, 'per event', ['Ticketing + registration', 'Multi-camera live stream', 'LED / screen integration', 'RSVP & guest app', 'On-site tech crew']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra check-in station', 'price' => 250_000], ['name' => 'Additional stream camera', 'price' => 500_000], ['name' => 'Custom guest app', 'price' => 900_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Concert Ticketing', 'Conference Registration', 'Live-streamed Wedding'];
    }

    protected function portfolioCaptions(): array
    {
        return ['The setup', 'Live'];
    }

    protected function portfolioFeedback(): string
    {
        return 'Check-in was fast and painless and the live stream was flawless — the tech just worked.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'eventsquare', 'email' => 'eventsquare@osep.test', 'first' => 'Emanuel', 'last' => 'Mushi',
             'business' => 'EventSquare', 'tagline' => 'Ticketing & attendance platform', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.75, 'reviews' => 30, 'jobs' => 190, 'response' => 2,
             'website' => 'https://eventsquare.co.tz', 'instagram' => 'https://instagram.com/eventsquare.tz',
             'description' => 'Dar es Salaam platform for event ticketing, payment processing and attendance management, with a vendor marketplace.'],
            ['key' => '19events', 'email' => '19events@osep.test', 'first' => 'Neema', 'last' => 'Kimaro',
             'business' => '19 Events', 'tagline' => 'Technology-focused event management', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 26, 'jobs' => 150, 'response' => 3,
             'website' => 'https://www.19events.co.tz', 'instagram' => 'https://instagram.com/19events.tz',
             'description' => 'A technology-focused Mikocheni event company maximising audience engagement before, during and after events.'],
            ['key' => 'hosannatech', 'email' => 'hosannatech@osep.test', 'first' => 'Baraka', 'last' => 'Mwakalinga',
             'business' => 'Hosanna Higher Technologies', 'tagline' => 'E-ticketing & entry control', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 22, 'jobs' => 140, 'response' => 3,
             'website' => 'https://hosannahighertech.co.tz', 'instagram' => 'https://instagram.com/hosannahighertech',
             'description' => 'Event e-ticketing and entry-control platform with QR codes, smart cards and mobile-money/bank integrations.'],
            ['key' => 'tukiio', 'email' => 'tukiio@osep.test', 'first' => 'Frank', 'last' => 'Sanga',
             'business' => 'Tukiio', 'tagline' => 'Online event ticketing', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 20, 'jobs' => 130, 'response' => 3,
             'website' => 'https://tukiio.com', 'instagram' => 'https://instagram.com/tukiio',
             'description' => 'Online event ticketing and registration for concerts, conferences and social events across Tanzania.'],
            ['key' => 'streamlive', 'email' => 'streamlivetz@osep.test', 'first' => 'Lawrence', 'last' => 'Mushi',
             'business' => 'StreamLive Tanzania', 'tagline' => 'Multi-camera live streaming', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 90, 'response' => 3,
             'website' => 'https://streamlivetz.example', 'instagram' => 'https://instagram.com/streamlivetz',
             'description' => 'Multi-camera live streaming of weddings, conferences and concerts to YouTube, Facebook and private links.'],
            ['key' => 'rsvphub', 'email' => 'rsvphub@osep.test', 'first' => 'Grace', 'last' => 'Laizer',
             'business' => 'RSVP Hub TZ', 'tagline' => 'Digital RSVP & guest apps', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 4, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 14, 'jobs' => 76, 'response' => 3,
             'website' => 'https://rsvphub.example', 'instagram' => 'https://instagram.com/rsvphubtz',
             'description' => 'Digital invitations, RSVP tracking and guest-management apps that keep the whole guest list in one place.'],
            ['key' => 'qrentry', 'email' => 'qrentry@osep.test', 'first' => 'Peter', 'last' => 'Gervas',
             'business' => 'QR Entry Systems', 'tagline' => 'QR check-in & access control', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 4, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 13, 'jobs' => 70, 'response' => 3,
             'website' => 'https://qrentry.example', 'instagram' => 'https://instagram.com/qrentrytz',
             'description' => 'QR-code check-in, badge printing and access control for fast, secure event entry.'],
            ['key' => 'smartbadge', 'email' => 'smartbadge@osep.test', 'first' => 'Anna', 'last' => 'Kaaya',
             'business' => 'SmartBadge Events', 'tagline' => 'Registration & badging', 'location' => 'Arusha, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 12, 'jobs' => 58, 'response' => 3,
             'website' => 'https://smartbadge.example', 'instagram' => 'https://instagram.com/smartbadgeevents',
             'description' => 'On-site registration, badge printing and delegate management for conferences and corporate events.'],
            ['key' => 'coastalstream', 'email' => 'coastalstream@osep.test', 'first' => 'Said', 'last' => 'Ali',
             'business' => 'Coastal Live Streaming', 'tagline' => 'Island event streaming', 'location' => 'Zanzibar, Tanzania',
             'years' => 4, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 11, 'jobs' => 44, 'response' => 4,
             'website' => 'https://coastalstream.example', 'instagram' => 'https://instagram.com/coastallivestreaming',
             'description' => 'Live streaming and AV integration for destination weddings and events across Zanzibar.'],
        ];
    }
}
