<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 12)->default('en')->after('avatar_url');
            $table->string('timezone', 64)->default('Africa/Dar_es_Salaam')->after('locale');
            $table->string('theme', 12)->default('light')->after('timezone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'timezone', 'theme']);
        });
    }
};
