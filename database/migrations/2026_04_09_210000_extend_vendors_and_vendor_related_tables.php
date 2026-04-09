<?php

use App\Support\MigrationCms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /** @var string CMS `users` table ref for FKs (e.g. `cms_db_staging.users` on MySQL). App DB is separate from CMS. */
        $cmsUsers = MigrationCms::usersTable();

        if (! Schema::hasColumn('vendors', 'kind')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->string('kind', 32)->default('active')->after('name')->index();
                $table->string('status', 32)->default('active')->after('kind')->index();
                $table->string('legal_name')->nullable()->after('status');
                $table->string('country', 64)->nullable()->after('legal_name');
                $table->string('website', 512)->nullable()->after('country');
                $table->string('industry', 255)->nullable()->after('website')->index();
                $table->text('main_products')->nullable()->after('industry');
                $table->decimal('contract_value', 15, 2)->nullable()->after('main_products');
                $table->decimal('estimated_cost', 15, 2)->nullable()->after('contract_value');
                $table->decimal('reference_price', 15, 2)->nullable()->after('estimated_cost');
                $table->decimal('vendor_score', 5, 2)->nullable()->after('reference_price');
                $table->decimal('score_price', 3, 1)->nullable()->after('vendor_score');
                $table->decimal('score_quality', 3, 1)->nullable()->after('score_price');
                $table->decimal('score_sla', 3, 1)->nullable()->after('score_quality');
                $table->decimal('score_support', 3, 1)->nullable()->after('score_sla');
                $table->string('risk_level', 16)->nullable()->after('score_support')->index();
                $table->text('internal_note')->nullable()->after('risk_level');
                $table->string('research_source', 64)->nullable()->after('internal_note');
                $table->text('research_note')->nullable()->after('research_source');
                $table->text('pros')->nullable()->after('research_note');
                $table->text('cons')->nullable()->after('pros');
                $table->unsignedTinyInteger('fit_score')->nullable()->after('cons');
                $table->decimal('review_rating_avg', 3, 2)->nullable()->after('fit_score');
                $table->softDeletes()->after('updated_at');
            });

            Schema::table('vendors', function (Blueprint $table) {
                $table->index('name');
            });
        }

        if (! Schema::hasTable('vendor_products')) {
            Schema::create('vendor_products', function (Blueprint $table) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('position')->default(0);
                $table->timestamps();
                $table->index(['vendor_id', 'position']);
            });
        }

        if (! Schema::hasTable('vendor_department')) {
            Schema::create('vendor_department', function (Blueprint $table) {
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
                $table->timestamps();
                $table->primary(['vendor_id', 'department_id']);
            });
        }

        if (! Schema::hasTable('vendor_reviews')) {
            Schema::create('vendor_reviews', function (Blueprint $table) use ($cmsUsers) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained($cmsUsers)->cascadeOnDelete();
                $table->decimal('rating', 2, 1);
                $table->text('body');
                $table->timestamps();
                $table->index(['vendor_id', 'created_at']);
            });
        }

        if (! Schema::hasTable('vendor_timelines')) {
            Schema::create('vendor_timelines', function (Blueprint $table) use ($cmsUsers) {
                $table->id();
                $table->foreignId('vendor_id')->constrained('vendors')->cascadeOnDelete();
                $table->string('phase', 64)->index();
                $table->dateTime('occurred_at');
                $table->foreignId('performed_by_user_id')->nullable()->constrained($cmsUsers)->nullOnDelete();
                $table->text('note')->nullable();
                $table->boolean('is_current')->default(false)->index();
                $table->timestamps();
                $table->index(['vendor_id', 'occurred_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_timelines');
        Schema::dropIfExists('vendor_reviews');
        Schema::dropIfExists('vendor_department');
        Schema::dropIfExists('vendor_products');

        if (Schema::hasColumn('vendors', 'kind')) {
            Schema::table('vendors', function (Blueprint $table) {
                $table->dropIndex(['name']);
                $table->dropSoftDeletes();
                $table->dropColumn([
                    'kind',
                    'status',
                    'legal_name',
                    'country',
                    'website',
                    'industry',
                    'main_products',
                    'contract_value',
                    'estimated_cost',
                    'reference_price',
                    'vendor_score',
                    'score_price',
                    'score_quality',
                    'score_sla',
                    'score_support',
                    'risk_level',
                    'internal_note',
                    'research_source',
                    'research_note',
                    'pros',
                    'cons',
                    'fit_score',
                    'review_rating_avg',
                ]);
            });
        }
    }
};
