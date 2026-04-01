<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payout_id')->constrained('payouts')->cascadeOnDelete();
            $table->nullableMorphs('source');
            $table->decimal('amount', 18, 2);
            $table->string('memo')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_items');
    }
};

