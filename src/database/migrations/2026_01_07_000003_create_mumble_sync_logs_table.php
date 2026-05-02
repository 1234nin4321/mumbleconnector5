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
        if (!Schema::hasTable('mumble_sync_logs')) {
            Schema::create('mumble_sync_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedInteger('seat_user_id')->nullable();
                $table->string('action');           // 'sync', 'remove', 'certificate', etc.
                $table->string('status');           // 'success', 'error', 'warning'
                $table->text('message')->nullable();
                $table->json('old_groups')->nullable();
                $table->json('new_groups')->nullable();
                $table->json('details')->nullable();
                $table->timestamps();

                $table->index('seat_user_id');
                $table->index('action');
                $table->index('status');
                $table->index('created_at');

                $table->foreign('seat_user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mumble_sync_logs');
    }
};
