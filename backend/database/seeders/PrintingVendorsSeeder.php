<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian printing / invitation companies (marketplace Printing Services
 * category). Real firms found publicly (Darcity, Print Galore, Jara Print, Golden
 * eCards, Tanzania Printers, The Site Weavers) plus representative studios; demo
 * contact + illustrative metrics. See {@see RealVendorSeeder}.
 */
class PrintingVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'printing-services';
    }

    protected function categoryFallbackName(): string
    {
        return 'Printing Services';
    }

    protected function imagePool(): array
    {
        return [
            '1524350876685-274059332603', '1519681393784-d120267933ba', '1606836576983-8b458e75221d',
            '1607344645866-009c320b63e0', '1583485088034-697b5bc54ccd', '1596526131083-e8c633c948d2',
            '1512909006721-3d6018887383',
        ];
    }

    protected function services(): array
    {
        return ['Wedding invitations', 'Programmes & menus', 'Signage & banners', 'Thank-you cards'];
    }

    protected function packages(): array
    {
        return [
            ['Invitation Set', 500, 1500, 'per event', ['Custom design', '100 printed invitations', 'Envelopes', '2 design revisions']],
            ['Full Stationery', 1800, 4000, 'per event', ['Invitations + RSVP cards', 'Programmes & menus', 'Table numbers & place cards', 'Matching envelopes']],
            ['Complete Suite', 4500, 9000, 'per event', ['Full stationery suite', 'Welcome signage & banners', 'Seating chart', 'Thank-you cards', 'Premium finishes (foil / letterpress)']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra 50 invitations', 'price' => 200_000], ['name' => 'Foil / letterpress finish', 'price' => 450_000], ['name' => 'Rush turnaround', 'price' => 300_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Invitation Suite', 'Event Signage', 'Menu & Programme'];
    }

    protected function portfolioCaptions(): array
    {
        return ['The suite', 'Finishing'];
    }

    protected function portfolioFeedback(): string
    {
        return 'Gorgeous stationery, crisp printing and delivered on time - exactly the look we wanted.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'darcity', 'email' => 'darcity@osep.test', 'first' => 'Daudi', 'last' => 'Mwakalinga',
             'business' => 'Darcity Printing', 'tagline' => 'Dar es Salaam’s go-to print house', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 10, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.75, 'reviews' => 43, 'jobs' => 420, 'response' => 2,
             'website' => 'https://darcity.tz', 'instagram' => 'https://instagram.com/darcitytz',
             'description' => 'A leading Dar es Salaam printing company producing invitations, flyers, business cards, banners and custom designs.'],
            ['key' => 'printgalore', 'email' => 'printgalore@osep.test', 'first' => 'Peter', 'last' => 'Nyagawa',
             'business' => 'Print Galore', 'tagline' => 'Printing & branding, done well', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 34, 'jobs' => 300, 'response' => 2,
             'website' => 'https://printgalore.co.tz', 'instagram' => 'https://instagram.com/printgalore',
             'description' => 'Comprehensive printing and branding - invitations, large-format banners, business cards, brochures and signage.'],
            ['key' => 'jaraprint', 'email' => 'jaraprint@osep.test', 'first' => 'Jamal', 'last' => 'Rashid',
             'business' => 'Jara Print', 'tagline' => 'Custom prints in 3–5 days', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 27, 'jobs' => 190, 'response' => 3,
             'website' => 'https://jaraprint.com', 'instagram' => 'https://instagram.com/jaraprint',
             'description' => 'Online printing for wedding invitations, banners, posters and more, delivered across Dar es Salaam and Dodoma.'],
            ['key' => 'goldenecards', 'email' => 'goldenecards@osep.test', 'first' => 'Gloria', 'last' => 'Kimaro',
             'business' => 'Golden eCards', 'tagline' => 'Digital invitations & RSVP', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 25, 'jobs' => 160, 'response' => 3,
             'website' => 'https://goldencreationss.com', 'instagram' => 'https://instagram.com/goldenecards',
             'description' => 'Digital wedding invitations delivered by WhatsApp, with built-in RSVP tracking, serving couples across Tanzania.'],
            ['key' => 'tzprinters', 'email' => 'tanzaniaprinters@osep.test', 'first' => 'Thomas', 'last' => 'Mwakalinga',
             'business' => 'Tanzania Printers', 'tagline' => 'Commercial & label printing', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 12, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 22, 'jobs' => 260, 'response' => 3,
             'website' => 'https://www.tanzaniaprinters.com', 'instagram' => 'https://instagram.com/tanzaniaprinters',
             'description' => 'A wide range of commercial, packaging and label printing, including elegant event and wedding stationery.'],
            ['key' => 'siteweavers', 'email' => 'siteweavers@osep.test', 'first' => 'Sospeter', 'last' => 'Mtui',
             'business' => 'The Site Weavers', 'tagline' => 'Digital & offset printing', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 19, 'jobs' => 130, 'response' => 3,
             'website' => 'https://www.siteweavers.co.tz', 'instagram' => 'https://instagram.com/thesiteweavers',
             'description' => 'High-quality digital and offset printing in Dar es Salaam, from invitations to signage and banners.'],
            ['key' => 'coastalpress', 'email' => 'coastalpress@osep.test', 'first' => 'Charles', 'last' => 'Sanga',
             'business' => 'Coastal Press', 'tagline' => 'Invitations & event print', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 90, 'response' => 3,
             'website' => 'https://coastalpress.example', 'instagram' => 'https://instagram.com/coastalpresstz',
             'description' => 'Wedding invitations, programmes and event print with clean design and quick turnaround.'],
            ['key' => 'elegantinvites', 'email' => 'elegantinvites@osep.test', 'first' => 'Elizabeth', 'last' => 'Mollel',
             'business' => 'Elegant Invitations TZ', 'tagline' => 'Bespoke wedding stationery', 'location' => 'Arusha, Tanzania',
             'years' => 5, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 14, 'jobs' => 58, 'response' => 4,
             'website' => 'https://elegantinvitestz.example', 'instagram' => 'https://instagram.com/elegantinvitationstz',
             'description' => 'Bespoke wedding invitations and stationery suites with premium finishes, based in Arusha.'],
            ['key' => 'stonetownprint', 'email' => 'stonetownprint@osep.test', 'first' => 'Suleiman', 'last' => 'Khamis',
             'business' => 'Stone Town Print & Design', 'tagline' => 'Design & print, Zanzibar', 'location' => 'Stone Town, Zanzibar',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 15, 'jobs' => 66, 'response' => 3,
             'website' => 'https://stonetownprint.example', 'instagram' => 'https://instagram.com/stonetownprint',
             'description' => 'Design and print studio in Stone Town producing invitations, signage and event stationery for island weddings.'],
        ];
    }
}
