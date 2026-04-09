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

        Schema::create('contract_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->foreignId('approver_id');
            $table->unsignedSmallInteger('step');
            $table->string('status', 32)->index();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->unique(['contract_id', 'step']);
        });

        Schema::table('contract_approvals', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('approver_id')->references('id')->on($cmsUsers)->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_approvals');
    }
};
