<?php

use App\Support\MigrationCms;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cmsUsers = MigrationCms::usersTable();

        Schema::create('task_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('user_id');
            $table->string('role', 16);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['task_id', 'user_id', 'role']);
            $table->index(['user_id', 'role']);
        });

        Schema::table('task_participants', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('user_id')->references('id')->on($cmsUsers)->cascadeOnDelete();
        });

        if (Schema::hasTable('tasks')) {
            DB::table('tasks')->whereNotNull('assignee_id')->orderBy('id')->chunk(200, function ($rows) {
                foreach ($rows as $t) {
                    DB::table('task_participants')->insert([
                        'task_id' => $t->id,
                        'user_id' => $t->assignee_id,
                        'role' => 'assignee',
                        'sort_order' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('task_participants');
    }
};
