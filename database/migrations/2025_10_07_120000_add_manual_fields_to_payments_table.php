<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (! Schema::hasColumn('payments', 'manual_status')) {
                $table->string('manual_status')->nullable()->after('provider_status'); // pending|approved|rejected
            }
            if (! Schema::hasColumn('payments', 'manual_proof_path')) {
                $table->string('manual_proof_path')->nullable()->after('manual_status');
            }
            if (! Schema::hasColumn('payments', 'manual_note')) {
                $table->text('manual_note')->nullable()->after('manual_proof_path');
            }
            if (! Schema::hasColumn('payments', 'manual_reviewed_by')) {
                $table->foreignId('manual_reviewed_by')->nullable()->after('manual_note')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('payments', 'manual_reviewed_at')) {
                $table->timestamp('manual_reviewed_at')->nullable()->after('manual_reviewed_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasColumn('payments', 'manual_reviewed_at')) {
                $table->dropColumn('manual_reviewed_at');
            }
            if (Schema::hasColumn('payments', 'manual_reviewed_by')) {
                $table->dropConstrainedForeignId('manual_reviewed_by');
            }
            if (Schema::hasColumn('payments', 'manual_note')) {
                $table->dropColumn('manual_note');
            }
            if (Schema::hasColumn('payments', 'manual_proof_path')) {
                $table->dropColumn('manual_proof_path');
            }
            if (Schema::hasColumn('payments', 'manual_status')) {
                $table->dropColumn('manual_status');
            }
        });
    }
};

