<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('customer_name')->nullable()->after('description');
            $table->string('customer_email')->nullable()->after('customer_name');
            $table->json('suppliers')->nullable()->after('customer_email');
            $table->json('process_timeline')->nullable()->after('suppliers');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['customer_name', 'customer_email', 'suppliers', 'process_timeline']);
        });
    }
};
