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

        Schema::table('projects', function (Blueprint $table) {
            $table->json('stakeholder_emails')->nullable()->after('description');
            $table->unsignedInteger('csat_invites_sent')->default(0)->after('stakeholder_emails');
            $table->timestamp('csat_survey_sent_at')->nullable()->after('csat_invites_sent');
        });

        Schema::create('kaizen_weekly_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->date('week_start');
            $table->timestamp('reminded_at');
            $table->timestamp('fulfilled_at')->nullable();
            $table->foreignId('kaizen_id')->nullable()->constrained('kaizens')->nullOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'week_start']);
        });

        Schema::table('kaizen_weekly_reminders', function (Blueprint $table) use ($cmsUsers) {
            $table->foreign('user_id')->references('id')->on($cmsUsers)->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kaizen_weekly_reminders');

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn(['stakeholder_emails', 'csat_invites_sent', 'csat_survey_sent_at']);
        });
    }
};
