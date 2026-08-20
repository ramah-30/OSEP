<?php

namespace Database\Seeders;

/**
 * Nine Tanzanian wedding transport / car-hire companies (marketplace
 * Transportation category). Real firms found publicly (Transporter, Shalom,
 * Bright, Rent a Driver, AJ) plus a few representative regional fleets; demo
 * contact + illustrative metrics. See {@see RealVendorSeeder}.
 */
class TransportVendorsSeeder extends RealVendorSeeder
{
    protected function categorySlug(): string
    {
        return 'transportation';
    }

    protected function categoryFallbackName(): string
    {
        return 'Transportation';
    }

    protected function imagePool(): array
    {
        return [
            '1503376780353-7e6692767b70', '1552519507-da3b142c6e3d', '1550355291-bbee04a92027',
            '1563720223185-11003d516935', '1502877338535-766e1452684a', '1511919884226-fd3cad34687c',
            '1549927681-0b673b8243ab', '1571127236794-81c0bbfe1ce3',
        ];
    }

    protected function services(): array
    {
        return ['Bridal car hire', 'Guest shuttle', 'Airport transfers', 'Chauffeur service'];
    }

    protected function packages(): array
    {
        return [
            ['Bridal Car', 400, 1000, 'per event', ['Chauffeur-driven', 'Ribbons & décor', 'Up to 6 hours', 'Fuel included']],
            ['Bridal + Guests', 1500, 4000, 'per event', ['Bridal car + minibus', 'Guest shuttle', 'Two chauffeurs', 'Full-day service']],
            ['VIP Fleet', 5000, 12000, 'per event', ['Luxury convoy', 'Multiple vehicles', 'Uniformed chauffeurs', 'Red-carpet arrival', 'Airport pickups']],
        ];
    }

    protected function addons(): array
    {
        return [['name' => 'Extra hour', 'price' => 120_000], ['name' => 'Additional vehicle', 'price' => 350_000], ['name' => 'Vintage car upgrade', 'price' => 600_000]];
    }

    protected function portfolioTitles(): array
    {
        return ['Wedding Convoy', 'Vintage Bridal Car', 'Guest Shuttle'];
    }

    protected function portfolioCaptions(): array
    {
        return ['Arrival', 'The fleet'];
    }

    protected function portfolioFeedback(): string
    {
        return 'Immaculate cars, punctual chauffeurs — our arrival felt like a red-carpet moment.';
    }

    protected function rows(): array
    {
        return [
            ['key' => 'transporter', 'email' => 'transportercar@osep.test', 'first' => 'Thomas', 'last' => 'Rweyemamu',
             'business' => 'Transporter Car Rental', 'tagline' => 'One-stop wedding car hire', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 10, 'level' => 'premium_partner', 'featured' => true, 'rating' => 4.75, 'reviews' => 47, 'jobs' => 320, 'response' => 2,
             'website' => 'https://transportercarrental.co.tz', 'instagram' => 'https://instagram.com/transportercarrental',
             'description' => 'A one-stop wedding car-hire company in Dar es Salaam with a fleet of sedans, SUVs, luxury cars, buses and minibuses.'],
            ['key' => 'shalom', 'email' => 'shalomcar@osep.test', 'first' => 'Samuel', 'last' => 'Mollel',
             'business' => 'Shalom Car Hire', 'tagline' => 'Car hire, tours & wedding cars', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.7, 'reviews' => 34, 'jobs' => 190, 'response' => 3,
             'website' => 'https://shalomcarrental.co.tz', 'instagram' => 'https://instagram.com/shalomcarrental',
             'description' => 'Dar es Salaam car hire, tours and transport, with dedicated wedding-day cars and chauffeurs.'],
            ['key' => 'bright', 'email' => 'brightcar@osep.test', 'first' => 'Brighton', 'last' => 'Massawe',
             'business' => 'Bright Car Rental', 'tagline' => 'Vintage & luxury bridal cars', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 9, 'level' => 'business_verified', 'featured' => true, 'rating' => 4.75, 'reviews' => 39, 'jobs' => 210, 'response' => 2,
             'website' => 'https://brightcarrentals.com', 'instagram' => 'https://instagram.com/bright_car_rental',
             'description' => 'Chauffeur-driven vintage and luxury wedding cars — Mercedes S-Class, Range Rover and more — across Dar es Salaam.'],
            ['key' => 'rentadriver', 'email' => 'rentadriver@osep.test', 'first' => 'Ramadhani', 'last' => 'Juma',
             'business' => 'Rent a Driver Tanzania', 'tagline' => 'Bridal cars to guest transport', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 26, 'jobs' => 150, 'response' => 3,
             'website' => 'https://rentadrivertanzania.com', 'instagram' => 'https://instagram.com/rentadrivertanzania',
             'description' => 'Luxury bridal cars and guest transport for weddings, with every detail handled elegantly.'],
            ['key' => 'ajcar', 'email' => 'ajcarhire@osep.test', 'first' => 'Ally', 'last' => 'Jamal',
             'business' => 'AJ Car Hire', 'tagline' => 'Weddings, airport & VIP', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 6, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 21, 'jobs' => 120, 'response' => 3,
             'website' => 'https://ajcarhire.example', 'facebook' => 'https://www.facebook.com/AJCARHIRE',
             'description' => 'Dar es Salaam car rental with driver, covering weddings, airport transfers and VIP transport.'],
            ['key' => 'coastallimo', 'email' => 'coastallimo@osep.test', 'first' => 'Charles', 'last' => 'Kessy',
             'business' => 'Coastal Limo Hire', 'tagline' => 'Stretch limos & luxury sedans', 'location' => 'Dar es Salaam, Tanzania',
             'years' => 5, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 18, 'jobs' => 84, 'response' => 3,
             'website' => 'https://coastallimo.example', 'instagram' => 'https://instagram.com/coastallimohire',
             'description' => 'Stretch limousines and luxury sedans for grand wedding entrances along the coast.'],
            ['key' => 'serengeticars', 'email' => 'serengetiexec@osep.test', 'first' => 'Emanuel', 'last' => 'Laizer',
             'business' => 'Serengeti Executive Cars', 'tagline' => 'Executive fleet, northern circuit', 'location' => 'Arusha, Tanzania',
             'years' => 8, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.65, 'reviews' => 22, 'jobs' => 110, 'response' => 3,
             'website' => 'https://serengetiexeccars.example', 'instagram' => 'https://instagram.com/serengetiexeccars',
             'description' => 'Executive cars and 4x4s for weddings, safaris and corporate transfers across northern Tanzania.'],
            ['key' => 'zanzibarcars', 'email' => 'zanzibarbridalcars@osep.test', 'first' => 'Salum', 'last' => 'Khamis',
             'business' => 'Zanzibar Bridal Cars', 'tagline' => 'Island wedding transport', 'location' => 'Zanzibar, Tanzania',
             'years' => 5, 'level' => 'email_verified', 'featured' => false, 'rating' => 4.55, 'reviews' => 15, 'jobs' => 60, 'response' => 4,
             'website' => 'https://zanzibarbridalcars.example', 'instagram' => 'https://instagram.com/zanzibarbridalcars',
             'description' => 'Chauffeured bridal cars and guest transfers for destination weddings across Zanzibar.'],
            ['key' => 'kiliprestige', 'email' => 'kiliprestige@osep.test', 'first' => 'Godlisten', 'last' => 'Mtei',
             'business' => 'Kilimanjaro Prestige Rentals', 'tagline' => 'Prestige cars, Moshi & Kilimanjaro', 'location' => 'Moshi, Tanzania',
             'years' => 7, 'level' => 'business_verified', 'featured' => false, 'rating' => 4.6, 'reviews' => 20, 'jobs' => 96, 'response' => 3,
             'website' => 'https://kiliprestige.example', 'instagram' => 'https://instagram.com/kiliprestige',
             'description' => 'Prestige and executive car hire for weddings and events around Moshi and Kilimanjaro.'],
        ];
    }
}
