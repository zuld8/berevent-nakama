<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('channel_id');
            $table->string('provider');
            $table->string('method_code');
            $table->json('config_json')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('channel_id')->references('id')->on('payment_channels')->cascadeOnUpdate()->restrictOnDelete();
            $table->unique(['provider', 'method_code']);
            $table->index(['channel_id', 'active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};

