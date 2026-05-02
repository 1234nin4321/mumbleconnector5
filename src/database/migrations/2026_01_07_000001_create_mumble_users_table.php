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
        if (!Schema::hasTable('mumble_users')) {
            Schema::create('mumble_users', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('seat_user_id')->unique();
                $table->string('mumble_username');
                $table->unsignedInteger('mumble_user_id')->nullable();
                $table->string('password_hash')->nullable();
                $table->string('certificate_hash')->nullable();
                $table->string('certificate_path')->nullable();
                $table->timestamp('certificate_generated_at')->nullable();
                $table->json('groups')->nullable();
                $table->timestamp('last_sync')->nullable();
                $table->boolean('needs_sync')->default(true);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('mumble_username');
                $table->index('needs_sync');
                $table->index('is_active');
                
                $table->foreign('seat_user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mumble_users');
    }
};
