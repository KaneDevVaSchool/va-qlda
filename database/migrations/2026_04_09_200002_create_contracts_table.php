<?php

use App\Support\MigrationCms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cmsUsers = MigrationCms::usersTable();

        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 32)->unique();
            $table->foreignId('vendor_id')->constrained('vendors')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->text('scope')->nullable();
            $table->string('status', 32)->index();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_value', 15, 2);
            $table->string('payment_cycle', 32);
            $table->foreignId('created_by');
            $table->foreignId('approved_by')->nullable();
            $table->timestamps();

            $table->index(['status', 'end_date']);
        });

        Schema::table('contracts', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('created_by')->references('id')->on($cmsUsers)->restrictOnDelete();
            $table->foreign('approved_by')->references('id')->on($cmsUsers)->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
