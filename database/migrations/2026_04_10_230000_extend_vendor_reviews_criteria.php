<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('vendor_reviews')) {
            return;
        }

        Schema::table('vendor_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('vendor_reviews', 'summary')) {
                $table->string('summary', 255)->nullable()->after('rating');
            }
            if (! Schema::hasColumn('vendor_reviews', 'context')) {
                $table->string('context', 32)->nullable()->after('summary')->index();
            }
            if (! Schema::hasColumn('vendor_reviews', 'quality_score')) {
                $table->decimal('quality_score', 2, 1)->nullable()->after('context');
            }
            if (! Schema::hasColumn('vendor_reviews', 'delivery_score')) {
                $table->decimal('delivery_score', 2, 1)->nullable()->after('quality_score');
            }
            if (! Schema::hasColumn('vendor_reviews', 'communication_score')) {
                $table->decimal('communication_score', 2, 1)->nullable()->after('delivery_score');
            }
            if (! Schema::hasColumn('vendor_reviews', 'would_recommend')) {
                $table->boolean('would_recommend')->nullable()->after('communication_score');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('vendor_reviews')) {
            return;
        }

        Schema::table('vendor_reviews', function (Blueprint $table) {
            if (Schema::hasColumn('vendor_reviews', 'would_recommend')) {
                $table->dropColumn('would_recommend');
            }
            if (Schema::hasColumn('vendor_reviews', 'communication_score')) {
                $table->dropColumn('communication_score');
            }
            if (Schema::hasColumn('vendor_reviews', 'delivery_score')) {
                $table->dropColumn('delivery_score');
            }
            if (Schema::hasColumn('vendor_reviews', 'quality_score')) {
                $table->dropColumn('quality_score');
            }
            if (Schema::hasColumn('vendor_reviews', 'context')) {
                $table->dropColumn('context');
            }
            if (Schema::hasColumn('vendor_reviews', 'summary')) {
                $table->dropColumn('summary');
            }
        });
    }
};
