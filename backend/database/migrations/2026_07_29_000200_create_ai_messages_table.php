<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One turn in an AI conversation. `meta` records how an assistant turn was
 * produced (agent used, provider/model, whether it was grounded) so the UI can
 * distinguish AI-generated content and the audit trail is complete.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained('ai_conversations')->cascadeOnDelete();
            $table->string('role'); // user|assistant|system
            $table->longText('content');
            $table->string('agent')->nullable();       // planning|budget|vendor|guest|analytics|conversation
            $table->string('model')->nullable();       // provider/model that answered
            $table->json('meta')->nullable();          // grounding summary, intent, etc.
            $table->timestamps();

            $table->index(['ai_conversation_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_messages');
    }
};
