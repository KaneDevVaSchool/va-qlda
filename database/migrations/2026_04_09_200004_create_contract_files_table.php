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

        Schema::create('contract_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->string('file_name', 512);
            $table->string('file_path', 2048);
            $table->string('file_type', 128)->nullable();
            $table->foreignId('uploaded_by');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('contract_files', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('uploaded_by')->references('id')->on($cmsUsers)->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_files');
    }
};
