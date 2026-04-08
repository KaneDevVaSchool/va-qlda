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

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('tasks')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('assignee_id')->nullable();
            $table->decimal('estimate_hours', 10, 2)->default(0);
            $table->decimal('actual_hours', 10, 2)->default(0);
            $table->unsignedTinyInteger('complexity')->default(1);
            $table->unsignedTinyInteger('impact')->default(1);
            $table->decimal('weight', 12, 6);
            $table->date('due_date')->nullable();
            $table->string('status', 32)->default('todo');
            $table->text('blocked_reason')->nullable();
            $table->timestamp('blocked_at')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('tasks', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('assignee_id')->references('id')->on($cmsUsers)->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
