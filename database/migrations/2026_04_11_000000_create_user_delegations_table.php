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

        Schema::create('user_delegations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delegator_id');
            $table->foreignId('delegatee_id');
            $table->string('scope', 64);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['delegator_id', 'delegatee_id']);
            $table->index('expires_at');
        });

        Schema::table('user_delegations', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('delegator_id')->references('id')->on($cmsUsers)->cascadeOnDelete();
            $table->foreign('delegatee_id')->references('id')->on($cmsUsers)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_delegations');
    }
};
