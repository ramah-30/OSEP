<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A 1:1 direct-message conversation between two users. The pair is stored
 * canonically (user_one_id is always the smaller id) with a unique constraint,
 * so there is exactly one conversation per pair — this is what guarantees two
 * people's messages never split across threads or bleed into someone else's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_one_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_two_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->unique(['user_one_id', 'user_two_id']);
            $table->index(['user_one_id', 'last_message_at']);
            $table->index(['user_two_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
