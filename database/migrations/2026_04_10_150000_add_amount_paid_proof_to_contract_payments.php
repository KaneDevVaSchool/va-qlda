<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contract_payments', function (Blueprint $table) {
            $table->decimal('amount_paid', 15, 2)->default(0)->after('amount');
            $table->foreignId('proof_file_id')->nullable()->after('paid_at');
        });

        Schema::table('contract_payments', function (Blueprint $table) {
            $table->foreign('proof_file_id')->references('id')->on('contract_files')->nullOnDelete();
        });

        if (Schema::hasTable('contract_payments')) {
            DB::table('contract_payments')->where('status', 'paid')->update([
                'amount_paid' => DB::raw('amount'),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('contract_payments', function (Blueprint $table) {
            $table->dropForeign(['proof_file_id']);
        });

        Schema::table('contract_payments', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'proof_file_id']);
        });
    }
};
