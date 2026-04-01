<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('title');
            $table->index('slug');
        });

        // Backfill slugs for existing rows
        $events = DB::table('events')->select('id', 'title', 'slug')->get();
        foreach ($events as $e) {
            $base = Str::slug((string) ($e->title ?? 'event'));
            if ($base === '') { $base = 'event'; }
            $slug = $base;
            $i = 1;
            while (DB::table('events')->where('slug', $slug)->where('id', '!=', $e->id)->exists()) {
                $slug = $base . '-' . (++$i);
            }
            DB::table('events')->where('id', $e->id)->update(['slug' => $slug]);
        }

        Schema::table('events', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropIndex(['slug']);
            $table->dropColumn('slug');
        });
    }
};

