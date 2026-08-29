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
        Schema::table('cleaning_assignments', function (Blueprint $table) {
            $table->unique(
                ['member_id', 'assignment_date'],
                'assignments_member_date_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cleaning_assignments', function (Blueprint $table) {
            $table->dropUnique('assignments_member_date_unique');
        });
    }
};
