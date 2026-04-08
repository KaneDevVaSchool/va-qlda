<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * App users live in the shared CMS database (connection `cms`).
     * If `cms_db_staging.users` already exists, this migration does nothing.
     */
    public function up(): void
    {
        if (Schema::connection('cms')->hasTable('users')) {
            return;
        }

        Schema::connection('cms')->create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // Do not drop shared CMS `users`.
    }
};
