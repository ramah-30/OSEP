<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Reference data — always seeded, safe in every environment.
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            CategorySeeder::class,
            VendorCategorySeeder::class,
        ]);

        // Sample tenant for local development and demos only.
        if (! app()->isProduction()) {
            $this->call(DemoSeeder::class);
            $this->call(PlannerDirectorySeeder::class);
            $this->call(DemoReviewersSeeder::class);
            $this->call(PlannerReviewsSeeder::class);
            $this->call(MarketplaceSeeder::class);
            $this->call(CateringVendorsSeeder::class);
            $this->call(PhotographerVendorsSeeder::class);
            $this->call(VideographerVendorsSeeder::class);
            $this->call(DecorVendorsSeeder::class);
            $this->call(DjVendorsSeeder::class);
            $this->call(McVendorsSeeder::class);
            $this->call(LiveBandVendorsSeeder::class);
            $this->call(MakeupVendorsSeeder::class);
            $this->call(TransportVendorsSeeder::class);
            $this->call(SecurityVendorsSeeder::class);
            $this->call(TentFurnitureVendorsSeeder::class);
            $this->call(PrintingVendorsSeeder::class);
            $this->call(EventEquipmentVendorsSeeder::class);
            $this->call(FloristVendorsSeeder::class);
            $this->call(GiftSupplierVendorsSeeder::class);
            $this->call(EntertainmentVendorsSeeder::class);
            $this->call(EventStaffingVendorsSeeder::class);
            $this->call(EventTechnologyVendorsSeeder::class);
            $this->call(CleaningVendorsSeeder::class);
            $this->call(HotelVendorSeeder::class);
            $this->call(VendorReviewsSeeder::class);
            $this->call(AccommodationReviewsSeeder::class);
            $this->call(FinanceSeeder::class);
        }
    }
}
