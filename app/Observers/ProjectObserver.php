<?php

namespace App\Observers;

use App\Mail\CsatSurveyInvitationMail;
use App\Models\Project;
use Illuminate\Support\Facades\Mail;

class ProjectObserver
{
    public function updated(Project $project): void
    {
        if (! $project->wasChanged('phase')) {
            return;
        }

        if ($project->phase !== 'done' || $project->type !== 'delivery') {
            return;
        }

        if ($project->csat_survey_sent_at !== null) {
            return;
        }

        $project->loadMissing('owner');
        $baseUrl = rtrim(config('app.url'), '/');
        $surveyUrl = $baseUrl.'/projects/'.$project->id;

        $emails = collect($project->stakeholder_emails ?? [])
            ->filter()
            ->map(fn ($e) => is_string($e) ? trim($e) : null)
            ->filter()
            ->values();

        if ($project->owner?->email) {
            $emails->push($project->owner->email);
        }

        $emails = $emails->unique()->values();
        if ($emails->isEmpty()) {
            return;
        }

        $sent = 0;
        foreach ($emails as $email) {
            Mail::mailer(config('mail.default'))->to($email)->send(
                new CsatSurveyInvitationMail($project, $surveyUrl)
            );
            $sent++;
        }

        $project->forceFill([
            'csat_invites_sent' => (int) $project->csat_invites_sent + $sent,
            'csat_survey_sent_at' => now(),
        ])->saveQuietly();
    }
}
