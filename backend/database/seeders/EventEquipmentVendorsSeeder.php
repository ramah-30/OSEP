<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian event-equipment / AV rental firms (marketplace Event Equipment
 * Rental category — sound, lighting, LED screens, staging, power). Real firms
 * found publicly (Aperture Media, LJBK Enterprises, Rent A Machine, Tanzania
 * Fixer) plus representative sound/lighting/LED/power brands, since named AV
 * firms are sparse online. Demo contact + illustrative metrics. See
 * {@see RealVendorSeeder}.
 */
class EventEquipmentVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'event-equipment-rental';
    }

    protected function categoryFallbackName(): string
    {
        return 'Event Equipment Rental';
    }

    protected function imagePool(): array
    {
        return [
            '1545454675-3531b543be5d', '1520170350707-b2da59970118', '1516873240891-4bf014598ab4',
            '1459749411175-04bf5292ceea', '1514525253161-7a46d19cd819', '1470225620780-dba8ba36b745',
            '1429962714451-bb934ecdc4ec', '1493225457124-a3eb161ffa5f', '1505236858219-8359eb29e329',
        ];
    }

    protected function services(): array
    {
        return ['Sound & PA hire', 'Stage lighting & effects', 'LED screens & projection', 'Staging, truss & power'];
    }

    protected function packages(): array
    {
        return [
            ['Sound & Lights', 1500, 3500, 'per event', ['PA system (up to 300 guests)', 'Stage lighting rig', 'One technician', 'Delivery & setup']],
            ['Full Event Production', 4000, 9000, 'per event', ['Line-array PA', 'Full lighting + haze', 'LED screen or projector', '2–3 technicians', 'Setup & teardown']],
            ['Concert Grade', 10000, 24000, 'per event', ['Concert PA & subs', 'Moving-head lighting show', 'LED video wall', 'Stage, truss & rigging', 'Backup generator', 'On-site crew']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra LED panel', 'price' => 500_000], ['name' => 'Backup generator', 'price' => 700_000], ['name' => 'Additional technician', 'price' => 200_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Reception', 'Corporate Conference', 'Concert / Festival'];
    }

    protected function portfolioCaptions(): array
    {
        return ['Rig & setup', 'Show time'];
    }

    protected function portfolioFeedback(): string
    {
        return 'Crystal-clear sound and a stunning light show — the technicians were professional and completely reliable.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'aperture', 'email' => 'aperturemedia@osep.test', 'first' => 'Ally', 'last' => 'Mkwawa',
             'business' => 'Aperture Media Equipment Rental', 'tagline' => 'Audio-visual equipment rental', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.75, 'reviews' => 40, 'jobs' => 220, 'response' => 2,
             'website' => 'https://aperturemedia.example', 'instagram' => 'https://instagram.com/aperturemediatz',
             'description' => 'A Dar es Salaam AV specialist (Baridi Street) renting sound systems, lighting, projection and screens for events and productions.'],
            ['key' => 'ljbk', 'email' => 'ljbkenterprises@osep.test', 'first' => 'Leonard', 'last' => 'Bukuku',
             'business' => 'LJBK Enterprises Ltd', 'tagline' => 'Staging, truss & event production', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 11, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.7, 'reviews' => 34, 'jobs' => 200, 'response' => 3,
             'website' => 'https://sites.google.com/view/ljbkenterprisesltd/home', 'instagram' => 'https://instagram.com/ljbkenterprises',
             'description' => 'Goba Road event company offering staging, truss, tents, lighting and full production setup across Tanzania.'],
            ['key' => 'rentamachine', 'email' => 'rentamachine@osep.test', 'first' => 'Rashid', 'last' => 'Machano',
             'business' => 'Rent A Machine Ltd', 'tagline' => 'Sound, power & event machines', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 28, 'jobs' => 160, 'response' => 3,
             'website' => 'https://rentamachine.example', 'instagram' => 'https://instagram.com/rentamachine',
             'description' => 'Dar es Salaam equipment-hire company supplying sound systems, generators and event machinery for functions of any size.'],
            ['key' => 'tzfixer', 'email' => 'tanzaniafixer@osep.test', 'first' => 'Tumaini', 'last' => 'Fabian',
             'business' => 'Tanzania Fixer', 'tagline' => 'Cinema-grade lighting & grip', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 10, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 22, 'jobs' => 90, 'response' => 3,
             'website' => 'https://www.tzfixer.com', 'instagram' => 'https://instagram.com/tanzaniafixer',
             'description' => 'Production-equipment rental with cinema-grade cameras, lighting, grip and crew — ideal for high-end event films and stages.'],
            ['key' => 'soundwave', 'email' => 'soundwavetz@osep.test', 'first' => 'Sadick', 'last' => 'Waziri',
             'business' => 'SoundWave Rentals TZ', 'tagline' => 'Pro sound & PA systems', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 19, 'jobs' => 110, 'response' => 3,
             'website' => 'https://soundwavetz.example', 'instagram' => 'https://instagram.com/soundwavetz',
             'description' => 'Professional line-array PA and sound-system hire with experienced engineers for weddings, concerts and conferences.'],
            ['key' => 'brightstage', 'email' => 'brightstage@osep.test', 'first' => 'Brighton', 'last' => 'Lweno',
             'business' => 'Bright Stage Lighting', 'tagline' => 'Stage lighting & effects', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 18, 'jobs' => 96, 'response' => 3,
             'website' => 'https://brightstage.example', 'instagram' => 'https://instagram.com/brightstagelighting',
             'description' => 'Stage and dancefloor lighting, uplighting, moving heads and haze/effects for weddings and shows.'],
            ['key' => 'ledvision', 'email' => 'ledvision@osep.test', 'first' => 'Lawrence', 'last' => 'Mushi',
             'business' => 'LED Vision Screens', 'tagline' => 'LED walls & video screens', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 17, 'jobs' => 80, 'response' => 3,
             'website' => 'https://ledvision.example', 'instagram' => 'https://instagram.com/ledvisionscreens',
             'description' => 'Indoor and outdoor LED video walls and projection for concerts, launches and large weddings.'],
            ['key' => 'powergen', 'email' => 'powergenhire@osep.test', 'first' => 'Peter', 'last' => 'Gervas',
             'business' => 'PowerGen Event Hire', 'tagline' => 'Generators & power distribution', 'location' => 'Arusha, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 16, 'jobs' => 88, 'response' => 3,
             'website' => 'https://powergenhire.example', 'instagram' => 'https://instagram.com/powergenhire',
             'description' => 'Silent generators and event power distribution for remote venues, safaris and outdoor celebrations.'],
            ['key' => 'coastalav', 'email' => 'coastalav@osep.test', 'first' => 'Salum', 'last' => 'Ali',
             'business' => 'Coastal AV Hire', 'tagline' => 'Island sound, light & screens', 'location' => 'Zanzibar, Tanzania',
             'years' => 6, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 14, 'jobs' => 58, 'response' => 4,
             'website' => 'https://coastalav.example', 'instagram' => 'https://instagram.com/coastalavhire',
             'description' => 'Sound, lighting and screen hire for destination weddings and events across Zanzibar.'],
        ];
    }
}
