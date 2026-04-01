<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaign_articles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('campaigns')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body_md')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('author_id');
            $table->foreignId('payout_id')->nullable()->constrained('payouts')->nullOnDelete();
            $table->timestamps();
            $table->index(['campaign_id', 'published_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaign_articles');
    }
};

