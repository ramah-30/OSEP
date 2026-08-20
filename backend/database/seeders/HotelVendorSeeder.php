<?php

namespace Database\Seeders;

use App\Enums\AccountType;
use App\Enums\UserStatus;
use App\Models\Accommodation;
use App\Models\User;
use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * One fully-detailed hotel account. Hotels register as vendors under the
 * "Hotels" category (added in {@see VendorCategorySeeder}); this account gets a
 * complete vendor profile and storefront, plus an owned accommodation listing
 * with room types so it shows up in both the vendor marketplace and the hotels
 * browse. Idempotent on the account email — safe to re-run.
 */
class HotelVendorSeeder extends Seeder
{
    private const EMAIL = 'hotel@osep.test';

    private const PASSWORD = 'Password123!';

    public function run(): void
    {
        $category = VendorCategory::where('slug', 'hotels')->first();

        $user = User::updateOrCreate(
            ['email' => self::EMAIL],
            [
                'first_name' => 'Amani',
                'last_name' => 'Hassan',
                'phone' => '+255 22 555 0140',
                'password' => Hash::make(self::PASSWORD),
                'account_type' => AccountType::Vendor,
                'country' => 'Tanzania',
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ],
        );
        $user->assignRole('vendor');

        $business = 'Amani Bay Hotel & Suites';

        $user->vendorProfile()->updateOrCreate([], [
            'business_name' => $business,
            'tagline' => 'Waterfront rooms & event stays in the heart of Dar',
            'category' => $category?->name ?? 'Hotels',
            'category_id' => $category?->id,
            'description' => "{$business} is a four-star waterfront hotel offering elegant rooms, suites and a rooftop event terrace. A favourite base for wedding parties and out-of-town guests, with block-booking rates, airport transfers and on-site catering.",
            'years_in_business' => 12,
            'location' => 'Msasani Peninsula, Dar es Salaam, Tanzania',
            'phone' => $user->phone,
            'contact_email' => self::EMAIL,
            'website' => 'https://amani-bay-hotel.example',
            'social_links' => ['instagram' => 'https://instagram.com/amanibayhotel'],
            'logo_url' => 'https://picsum.photos/seed/amani-hotel-logo/200/200',
            'cover_image_url' => 'https://picsum.photos/seed/amani-hotel-cover/1200/400',
            'verification_status' => 'verified',
            'verification_level' => 'business_verified',
            'availability_status' => 'available',
            'profile_views' => 1240,
            'pending_requests' => 3,
            'response_time_hours' => 2,
            'completed_jobs' => 86,
            'reviews_count' => 47,
            'rating' => 4.6,
            'is_featured' => true,
            'is_suspended' => false,
        ]);

        $this->storefront($user, $business);
        $this->accommodation($user, $business);
    }

    private function storefront(User $vendor, string $business): void
    {
        $services = [
            'Guest room block booking',
            'Rooftop event terrace hire',
            'On-site catering & banqueting',
        ];
        foreach ($services as $i => $name) {
            $vendor->vendorServices()->updateOrCreate(
                ['name' => $name],
                [
                    'category_id' => $vendor->vendorProfile->category_id,
                    'description' => "{$name} from {$business}.",
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }

        $packages = [
            ['Standard Stay', 180_000, ['Standard double room', 'Breakfast for two', 'Free Wi-Fi', 'Late checkout on request']],
            ['Wedding Guest Block', 3_500_000, ['10 rooms for two nights', 'Group breakfast', 'Airport shuttle', 'Dedicated coordinator']],
            ['Rooftop Reception', 6_500_000, ['Terrace hire (up to 150 guests)', 'Banquet catering', 'Bar service', 'Sound system & lighting']],
        ];
        foreach ($packages as $i => [$name, $price, $inclusions]) {
            $vendor->vendorPackages()->updateOrCreate(
                ['name' => $name],
                [
                    'description' => "The {$name} package from {$business}.",
                    'price' => $price,
                    'currency' => 'TZS',
                    'price_unit' => $name === 'Standard Stay' ? 'per night' : 'per event',
                    'inclusions' => $inclusions,
                    'addons' => [['name' => 'Extra room night', 'price' => 180_000], ['name' => 'Airport transfer', 'price' => 60_000]],
                    'terms' => '50% deposit to confirm. Balance due 7 days before arrival.',
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (['Beachfront Wedding Weekend', 'Corporate Conference Stay', 'Rooftop Anniversary Dinner'] as $i => $title) {
            $vendor->vendorPortfolios()->updateOrCreate(
                ['title' => $title],
                [
                    'description' => "A {$title} hosted at {$business}.",
                    'event_type' => $title,
                    'event_date' => now()->subMonths(($i + 1) * 3)->toDateString(),
                    'cover_url' => "https://picsum.photos/seed/amani-p{$i}/800/600",
                    'media' => [
                        ['type' => 'image', 'url' => "https://picsum.photos/seed/amani-p{$i}a/800/600", 'caption' => 'The venue'],
                        ['type' => 'image', 'url' => "https://picsum.photos/seed/amani-p{$i}b/800/600", 'caption' => 'The celebration'],
                    ],
                    'client_feedback' => 'Beautiful rooms and seamless service — our guests loved it.',
                    'is_case_study' => $i === 0,
                    'sort_order' => $i,
                ],
            );
        }

        foreach (range(0, 20) as $d) {
            $date = now()->addDays($d)->toDateString();
            $status = match (true) {
                $d % 7 === 3 => 'fully_booked',
                $d % 5 === 2 => 'reserved',
                default => 'available',
            };
            $vendor->vendorAvailability()->updateOrCreate(['date' => $date], ['status' => $status]);
        }
    }

    private function accommodation(User $owner, string $name): void
    {
        $rooms = [
            ['name' => 'Deluxe Bay Room', 'price' => 220_000, 'capacity' => 2, 'beds' => '1 Queen', 'size' => 32, 'total' => 24,
                'amenities' => ['Sea view', 'Balcony', 'Air conditioning', 'Minibar'], 'desc' => 'A bright room overlooking Msasani Bay.',
                'image' => 'https://images.unsplash.com/photo-1611892440504-42a792e24d32?w=900&q=80'],
            ['name' => 'Executive Suite', 'price' => 420_000, 'capacity' => 2, 'beds' => '1 King', 'size' => 55, 'total' => 8,
                'amenities' => ['Living area', 'Sea view', 'Work desk', 'Butler service'], 'desc' => 'A spacious suite with a separate lounge.',
                'image' => 'https://images.unsplash.com/photo-1590490360182-c33d57733427?w=900&q=80'],
            ['name' => 'Standard Twin', 'price' => 160_000, 'capacity' => 2, 'beds' => '2 Twin', 'size' => 26, 'total' => 30,
                'amenities' => ['City view', 'Air conditioning', 'Work desk'], 'desc' => 'A comfortable twin room for guests in town for the celebration.',
                'image' => 'https://images.unsplash.com/photo-1595576508898-0ad5c879a061?w=900&q=80'],
        ];

        $hotel = Accommodation::updateOrCreate(
            ['slug' => Str::slug($name)],
            [
                'owner_id' => $owner->id,
                'name' => $name,
                'description' => 'A four-star waterfront hotel on the Msasani Peninsula with elegant rooms, a rooftop event terrace and on-site catering — an ideal base for wedding parties and visiting guests.',
                'star_rating' => 4,
                'city' => 'Dar es Salaam',
                'location' => 'Msasani Peninsula, Dar es Salaam',
                'address' => 'Msasani Peninsula, Dar es Salaam, Tanzania',
                'amenities' => ['Free Wi-Fi', 'Rooftop terrace', 'On-site restaurant', 'Airport transfer', 'Swimming pool', 'Event catering', 'Breakfast included'],
                'cover_image_url' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=1200&q=80',
                'currency' => 'TZS',
                'price_from' => collect($rooms)->min('price'),
                'check_in_time' => '14:00',
                'check_out_time' => '11:00',
                'policies' => 'Free cancellation up to 7 days before check-in. Group rates available for wedding blocks of 10+ rooms.',
                'contact_email' => self::EMAIL,
                'contact_phone' => $owner->phone,
                'is_featured' => true,
                'is_published' => true,
            ],
        );

        foreach ($rooms as $room) {
            $hotel->roomTypes()->updateOrCreate(
                ['name' => $room['name']],
                [
                    'description' => $room['desc'],
                    'price_per_night' => $room['price'],
                    'currency' => 'TZS',
                    'capacity' => $room['capacity'],
                    'bed_configuration' => $room['beds'],
                    'size_sqm' => $room['size'],
                    'amenities' => $room['amenities'],
                    'image_url' => $room['image'],
                    'total_rooms' => $room['total'],
                    'is_active' => true,
                ],
            );
        }
    }
}
