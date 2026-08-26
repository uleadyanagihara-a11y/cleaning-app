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
        Schema::table('cleaning_roles', function (Blueprint $table) {
            $table->unsignedSmallInteger('required_member_count')
                ->default(1)
                ->after('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaning_roles', function (Blueprint $table) {
            $table->dropColumn('required_member_count');
        });
    }
};
