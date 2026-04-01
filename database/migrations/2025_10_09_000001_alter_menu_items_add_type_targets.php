<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->string('type')->nullable()->after('parent_id');
            $table->foreignId('news_id')->nullable()->after('page_id')->constrained('news')->nullOnDelete();
            $table->foreignId('campaign_id')->nullable()->after('news_id')->constrained('campaigns')->nullOnDelete();
            $table->index(['type']);
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table) {
            $table->dropIndex(['type']);
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropConstrainedForeignId('news_id');
            $table->dropColumn('type');
        });
    }
};

