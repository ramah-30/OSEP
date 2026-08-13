<?php

namespace Database\Seeders;

use App\Models\VendorCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The marketplace's default vendor taxonomy (spec's supported categories).
 * Reference data — safe in every environment. Admins add custom categories on
 * top through the admin tools.
 */
class VendorCategorySeeder extends Seeder
{
    /** @var array<int, array{name:string, icon:string}> */
    private array $categories = [
        ['name' => 'Caterers', 'icon' => 'UtensilsCrossed'],
        ['name' => 'Decorators', 'icon' => 'Sparkles'],
        ['name' => 'Photographers', 'icon' => 'Camera'],
        ['name' => 'Videographers', 'icon' => 'Video'],
        ['name' => 'DJs', 'icon' => 'Disc3'],
        ['name' => 'MCs', 'icon' => 'Mic'],
        ['name' => 'Live Bands', 'icon' => 'Music'],
        ['name' => 'Florists', 'icon' => 'Flower2'],
        ['name' => 'Makeup Artists', 'icon' => 'Brush'],
        ['name' => 'Transportation', 'icon' => 'Car'],
        ['name' => 'Security Services', 'icon' => 'ShieldCheck'],
        ['name' => 'Event Equipment Rental', 'icon' => 'Speaker'],
        ['name' => 'Tent & Furniture Rental', 'icon' => 'Tent'],
        ['name' => 'Printing Services', 'icon' => 'Printer'],
        ['name' => 'Entertainment', 'icon' => 'PartyPopper'],
        ['name' => 'Event Staffing', 'icon' => 'Users'],
        ['name' => 'Cleaning Services', 'icon' => 'Sparkle'],
        ['name' => 'Gift Suppliers', 'icon' => 'Gift'],
        ['name' => 'Event Technology', 'icon' => 'MonitorSmartphone'],
    ];

    public function run(): void
    {
        foreach ($this->categories as $i => $category) {
            VendorCategory::updateOrCreate(
                ['slug' => Str::slug($category['name'])],
                [
                    'name' => $category['name'],
                    'icon' => $category['icon'],
                    'is_custom' => false,
                    'is_active' => true,
                    'sort_order' => $i,
                ],
            );
        }
    }
}
