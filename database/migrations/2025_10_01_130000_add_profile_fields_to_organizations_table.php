<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('logo_path')->nullable()->after('phone');
            $table->text('summary')->nullable()->after('phone');
            $table->text('commitment')->nullable()->after('summary');
            $table->text('address')->nullable()->after('commitment');
            $table->decimal('lat', 10, 7)->nullable()->after('address');
            $table->decimal('lng', 10, 7)->nullable()->after('lat');
            $table->json('social_json')->nullable()->after('meta_json');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['logo_path', 'summary', 'commitment', 'address', 'lat', 'lng', 'social_json']);
        });
    }
};

