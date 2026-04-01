<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaign_media', function (Blueprint $table) {
            $table->string('platform')->default('desktop')->after('type');
        });
    }

    public function down(): void
    {
        Schema::table('campaign_media', function (Blueprint $table) {
            $table->dropColumn('platform');
        });
    }
};

