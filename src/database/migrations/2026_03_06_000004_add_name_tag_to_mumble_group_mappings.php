<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mumble_group_mappings') && !Schema::hasColumn('mumble_group_mappings', 'name_tag')) {
            Schema::table('mumble_group_mappings', function (Blueprint $table) {
                // Optional tag appended to username for users matching this mapping
                // e.g. " [FC]", " [DIR]", " | Leadership"
                $table->string('name_tag')->nullable()->after('mumble_group');
            });
        }
    }

    public function down(): void
    {
        Schema::table('mumble_group_mappings', function (Blueprint $table) {
            $table->dropColumn('name_tag');
        });
    }
};
