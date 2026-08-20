<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A planner's client roster. Until now a planner's clients were derived purely
 * from their events, so a client added standalone (the "New client" button)
 * had no link to the planner and vanished from the list. This pivot gives the
 * roster a home, independent of whether the client sits on an event yet.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planner_client', function (Blueprint $table) {
            $table->id();
            $table->foreignId('planner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['planner_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planner_client');
    }
};
