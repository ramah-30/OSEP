<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_portfolios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('event_type')->nullable();
            $table->date('event_date')->nullable();
            $table->string('cover_url')->nullable();
            $table->json('media')->nullable();            // array of { type: image|video, url, caption }
            $table->text('client_feedback')->nullable();
            $table->boolean('is_case_study')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index('vendor_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_portfolios');
    }
};
