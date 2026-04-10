<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('replay_url')->nullable()->after('status');
            $table->unsignedInteger('replay_price')->nullable()->after('replay_url')
                  ->comment('null = replay gratis untuk pemilik tiket, 0 = gratis semua, >0 = harga beli replay');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['replay_url', 'replay_price']);
        });
    }
};
