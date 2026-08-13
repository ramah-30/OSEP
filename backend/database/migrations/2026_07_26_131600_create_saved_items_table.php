<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('saved_collections')->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('venue_id')->nullable()->constrained('marketplace_venues')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamps();

            // A given vendor/venue can only sit once in a collection.
            $table->unique(['collection_id', 'vendor_id']);
            $table->unique(['collection_id', 'venue_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_items');
    }
};
