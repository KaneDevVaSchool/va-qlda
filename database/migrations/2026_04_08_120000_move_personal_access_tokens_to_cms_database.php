<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $cms = 'cms';
        $appConn = config('database.default');

        // Tests (sqlite): app + cms share one file — keep single `personal_access_tokens` table.
        if ($appConn === 'sqlite') {
            return;
        }

        if (! Schema::connection($cms)->hasTable('personal_access_tokens')) {
            Schema::connection($cms)->create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        $appDb = config("database.connections.{$appConn}.database");
        $cmsDb = config("database.connections.{$cms}.database");

        if (! Schema::connection($appConn)->hasTable('personal_access_tokens')) {
            return;
        }

        $cmsEmpty = DB::connection($cms)->table('personal_access_tokens')->count() === 0;
        $appCount = DB::connection($appConn)->table('personal_access_tokens')->count();

        if ($cmsEmpty && $appCount > 0) {
            DB::statement(
                "INSERT INTO `{$cmsDb}`.`personal_access_tokens` SELECT * FROM `{$appDb}`.`personal_access_tokens`"
            );
        }

        Schema::connection($appConn)->dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
        $cms = 'cms';
        $appConn = config('database.default');

        if ($appConn === 'sqlite') {
            return;
        }

        if (! Schema::connection($appConn)->hasTable('personal_access_tokens')) {
            Schema::connection($appConn)->create('personal_access_tokens', function (Blueprint $table) {
                $table->id();
                $table->morphs('tokenable');
                $table->string('name');
                $table->string('token', 64)->unique();
                $table->text('abilities')->nullable();
                $table->timestamp('last_used_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();
            });
        }

        $appDb = config("database.connections.{$appConn}.database");
        $cmsDb = config("database.connections.{$cms}.database");

        if (Schema::connection($cms)->hasTable('personal_access_tokens')) {
            $cmsCount = DB::connection($cms)->table('personal_access_tokens')->count();
            if ($cmsCount > 0 && DB::connection($appConn)->table('personal_access_tokens')->count() === 0) {
                DB::statement(
                    "INSERT INTO `{$appDb}`.`personal_access_tokens` SELECT * FROM `{$cmsDb}`.`personal_access_tokens`"
                );
            }
            Schema::connection($cms)->dropIfExists('personal_access_tokens');
        }
    }
};
