<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('summary')->nullable();
            $table->longText('description_md')->nullable();
            $table->decimal('target_amount', 18, 2)->default(0);
            $table->decimal('raised_amount', 18, 2)->default(0);
            $table->string('status')->default('draft'); // draft, active, paused, ended
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('settings_json')->nullable();
            $table->json('location_json')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};

