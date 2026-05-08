<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Full-text indexes are MySQL/PostgreSQL only — no-op on SQLite (dev/test)
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->fullText(['title', 'description']);
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('courses', function (Blueprint $table) {
            $table->dropFullText(['title', 'description']);
        });
    }
};
