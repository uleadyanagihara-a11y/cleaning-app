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
        Schema::create('cleaning_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')
                ->constrained()
                ->restrictOnDelete();
            $table->foreignId('cleaning_role_id')
                ->constrained()
                ->restrictOnDelete();
            $table->date('assignment_date')->index();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['member_id', 'cleaning_role_id', 'assignment_date'],
                'assignments_member_role_date_unique',
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cleaning_assignments');
    }
};
