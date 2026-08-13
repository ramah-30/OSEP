<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grows the existing Phase 2 vendor_profiles row into a full marketplace
 * storefront: cover art, business facts, a category link, a progressive
 * verification level (distinct from the pending/verified review status) and the
 * admin featured / suspended flags.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('category')
                ->constrained('vendor_categories')->nullOnDelete();
            $table->string('tagline')->nullable()->after('business_name');
            $table->string('cover_image_url')->nullable()->after('logo_url');
            $table->string('contact_email')->nullable()->after('phone');
            $table->unsignedInteger('years_in_business')->nullable()->after('description');
            $table->unsignedInteger('response_time_hours')->nullable()->after('pending_requests');
            $table->string('verification_level')->default('unverified')->index()->after('verification_status');
            $table->boolean('is_featured')->default(false)->index()->after('rating');
            $table->boolean('is_suspended')->default(false)->index()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_profiles', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn([
                'category_id', 'tagline', 'cover_image_url', 'contact_email',
                'years_in_business', 'response_time_hours', 'verification_level',
                'is_featured', 'is_suspended',
            ]);
        });
    }
};
