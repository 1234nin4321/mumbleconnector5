<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('mumble_group_mappings')) {
            Schema::create('mumble_group_mappings', function (Blueprint $table) {
                $table->id();
                $table->string('seat_type');        // 'role', 'squad', 'corporation', 'alliance'
                $table->string('seat_identifier');  // Role name, Squad ID, Corp ID, or Alliance ID
                $table->string('seat_name')->nullable(); // Human-readable name
                $table->string('mumble_group');     // Mumble group name
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(['seat_type', 'seat_identifier']);
                $table->index('seat_type');
                $table->index('is_active');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mumble_group_mappings');
    }
};
