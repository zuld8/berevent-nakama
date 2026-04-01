<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->unsignedInteger('session_count')->default(1);
            $table->enum('mode', ['online', 'offline', 'both'])->default('online');
            $table->dateTime('start_date')->nullable();
            $table->dateTime('end_date')->nullable();
            $table->enum('price_type', ['fixed', 'donation'])->default('fixed');
            $table->decimal('price', 18, 2)->nullable();
            $table->string('cover_path')->nullable();
            $table->enum('type', ['umum', 'khusus'])->default('umum');
            $table->longText('description')->nullable();
            $table->enum('status', ['draft', 'published', 'completed'])->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
