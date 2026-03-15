<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('file')) {
            return;
        }

        Schema::table('file', function (Blueprint $table) {
            if (!Schema::hasColumn('file', 'filename')) {
                $table->string('filename')->after('adminID');
            }
            if (!Schema::hasColumn('file', 'path')) {
                $table->string('path')->after('filename');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('file')) {
            return;
        }

        Schema::table('file', function (Blueprint $table) {
            if (Schema::hasColumn('file', 'path')) {
                $table->dropColumn('path');
            }
            if (Schema::hasColumn('file', 'filename')) {
                $table->dropColumn('filename');
            }
        });
    }
};
