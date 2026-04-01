<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->string('meta_title')->nullable()->after('summary');
            $table->text('meta_description')->nullable()->after('meta_title');
            $table->string('meta_image_url')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['meta_title', 'meta_description', 'meta_image_url']);
        });
    }
};

