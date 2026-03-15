<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_id')->default(0);
            $table->string('employee_id')->nullable();
            $table->string('employee_name')->nullable();
            $table->string('department')->nullable();
            $table->string('period_raw')->nullable();
            $table->date('period_date')->nullable();
            $table->json('attendance')->nullable();
            $table->string('document_path')->nullable();
            $table->timestamps();

            $table->index(['admin_id', 'department']);
            $table->index(['admin_id', 'period_date']);
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('attendance_records')) {
            return;
        }

        Schema::dropIfExists('attendance_records');
    }
};
