<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('event_id')->constrained('events');
            $table->foreignId('material_id')->nullable()->constrained('event_materials');
            $table->timestamp('checked_in_at');
            $table->json('meta_json')->nullable();
            $table->timestamps();
            $table->unique(['ticket_id','material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

