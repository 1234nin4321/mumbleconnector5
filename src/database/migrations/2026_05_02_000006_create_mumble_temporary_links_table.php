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
        if (!Schema::hasTable('mumble_temporary_links')) {
            Schema::create('mumble_temporary_links', function (Blueprint $table) {
                $table->id();
                $table->string('token')->unique();
                $table->string('display_name');
                $table->string('mumble_username');
                $table->string('password');
                $table->integer('mumble_user_id')->nullable();
                $table->timestamp('expires_at');
                $table->foreignId('created_by')->constrained('users');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mumble_temporary_links');
    }
};
