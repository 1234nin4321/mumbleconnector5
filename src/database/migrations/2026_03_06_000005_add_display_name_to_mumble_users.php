<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mumble_users', function (Blueprint $table) {
            // Stores the formatted Mumble display name (with ticker/tags)
            // mumble_username stays as the stable character name
            $table->string('mumble_display_name')->nullable()->after('mumble_username');
        });
    }

    public function down(): void
    {
        Schema::table('mumble_users', function (Blueprint $table) {
            $table->dropColumn('mumble_display_name');
        });
    }
};
