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
        $mysql = 'mysql';

        // PHPUnit: sqlite app + cms share one file — one physical `personal_access_tokens` table.
        if (config("database.connections.{$cms}.driver") === 'sqlite'
            && config("database.connections.{$cms}.database") === config("database.connections.{$mysql}.database")) {
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

        $appDb = config("database.connections.{$mysql}.database");
        $cmsDb = config("database.connections.{$cms}.database");

        if (! Schema::connection($mysql)->hasTable('personal_access_tokens')) {
            return;
        }

        $cmsEmpty = DB::connection($cms)->table('personal_access_tokens')->count() === 0;
        $appCount = DB::connection($mysql)->table('personal_access_tokens')->count();

        if ($cmsEmpty && $appCount > 0) {
            DB::statement(
                "INSERT INTO `{$cmsDb}`.`personal_access_tokens` SELECT * FROM `{$appDb}`.`personal_access_tokens`"
            );
        }

        Schema::connection($mysql)->dropIfExists('personal_access_tokens');
    }

    public function down(): void
    {
        $cms = 'cms';
        $mysql = 'mysql';

        if (config("database.connections.{$cms}.driver") === 'sqlite'
            && config("database.connections.{$cms}.database") === config("database.connections.{$mysql}.database")) {
            return;
        }

        if (! Schema::connection($mysql)->hasTable('personal_access_tokens')) {
            Schema::connection($mysql)->create('personal_access_tokens', function (Blueprint $table) {
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

        $appDb = config("database.connections.{$mysql}.database");
        $cmsDb = config("database.connections.{$cms}.database");

        if (Schema::connection($cms)->hasTable('personal_access_tokens')) {
            $cmsCount = DB::connection($cms)->table('personal_access_tokens')->count();
            if ($cmsCount > 0 && DB::connection($mysql)->table('personal_access_tokens')->count() === 0) {
                DB::statement(
                    "INSERT INTO `{$appDb}`.`personal_access_tokens` SELECT * FROM `{$cmsDb}`.`personal_access_tokens`"
                );
            }
            Schema::connection($cms)->dropIfExists('personal_access_tokens');
        }
    }
};
