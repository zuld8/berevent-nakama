<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            if (! Schema::hasColumn('heroes', 'event_id')) {
                $table->foreignId('event_id')->nullable()->after('campaign_id')->constrained('events')->nullOnDelete();
                $table->index(['event_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('heroes', function (Blueprint $table) {
            if (Schema::hasColumn('heroes', 'event_id')) {
                $table->dropConstrainedForeignId('event_id');
            }
        });
    }
};

