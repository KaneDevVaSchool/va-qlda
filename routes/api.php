<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CsatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\EvaluationPeerController;
use App\Http\Controllers\Api\InnovationIdeaController;
use App\Http\Controllers\Api\KaizenController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectDocumentController;
use App\Http\Controllers\Api\ProjectImportController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskAttachmentController;
use App\Http\Controllers\Api\TaskBulkController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\UserLookupController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/login/mfa', [AuthController::class, 'loginMfa'])->middleware('throttle:login');

Route::get('/auth/google/config', [GoogleOAuthController::class, 'config']);
Route::get('/auth/google/redirect', [GoogleOAuthController::class, 'redirectUrl'])->middleware('throttle:google_oauth');
Route::post('/auth/google/exchange', [GoogleOAuthController::class, 'exchange'])->middleware('throttle:google_oauth');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/users/lookup', [UserLookupController::class, 'index']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::get('/kpi/current', [KpiController::class, 'current']);
    Route::get('/kpi/benchmark', [KpiController::class, 'benchmark']);
    Route::get('/kpi/snapshots', [KpiController::class, 'snapshots']);
    Route::post('/kpi/snapshot-run', [KpiController::class, 'runSnapshot']);

    Route::get('/reports/weekly-status.pdf', [ReportController::class, 'weeklyStatusPdf']);
    Route::get('/reports/export/projects.csv', [ReportController::class, 'exportCsv']);
    Route::get('/reports/export/projects-filtered.csv', [ReportController::class, 'exportProjectsFilteredCsv']);
    Route::get('/reports/export/projects-filtered.json', [ReportController::class, 'exportProjectsFilteredJson']);
    Route::get('/reports/export/projects-filtered.pdf', [ReportController::class, 'exportProjectsFilteredPdf']);
    Route::get('/reports/import/projects-template.csv', [ProjectImportController::class, 'templateCsv']);
    Route::get('/reports/import/projects-template.json', [ProjectImportController::class, 'templateJson']);
    Route::get('/reports/kaizen-impact', [ReportController::class, 'kaizenImpactSummary']);

    Route::post('projects/import/preview', [ProjectImportController::class, 'preview'])->middleware('throttle:30,1');
    Route::post('projects/import/commit', [ProjectImportController::class, 'commit'])->middleware('throttle:20,1');
    Route::post('projects/bulk', [ProjectController::class, 'bulk']);
    Route::post('projects/bulk-destroy', [ProjectController::class, 'bulkDestroy']);
    Route::get('projects/tab-counts', [ProjectController::class, 'tabCounts']);
    Route::get('projects/label-suggestions', [ProjectController::class, 'labelSuggestions']);
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/gantt', [ProjectController::class, 'gantt']);
    Route::get('projects/{project}/attachments', [ProjectController::class, 'attachments']);
    Route::get('projects/{project}/documents', [ProjectDocumentController::class, 'index']);
    Route::post('projects/{project}/documents', [ProjectDocumentController::class, 'store']);
    Route::post('projects/{project}/documents/upload', [ProjectDocumentController::class, 'upload']);
    Route::get('project-documents/{document}/download', [ProjectDocumentController::class, 'download'])->whereNumber('document');
    Route::patch('project-documents/{document}', [ProjectDocumentController::class, 'update'])->whereNumber('document');
    Route::delete('project-documents/{document}', [ProjectDocumentController::class, 'destroy'])->whereNumber('document');
    Route::patch('projects/{project}/archive', [ProjectController::class, 'archive']);
    Route::post('projects/{project}/duplicate', [ProjectController::class, 'duplicate']);

    Route::get('projects/{project}/tasks', [TaskController::class, 'index']);
    Route::post('projects/{project}/tasks', [TaskController::class, 'store']);
    Route::put('tasks/{task}', [TaskController::class, 'update']);
    Route::delete('tasks/{task}', [TaskController::class, 'destroy']);
    Route::post('tasks/bulk', [TaskBulkController::class, 'update']);

    Route::get('tasks/{task}/comments', [TaskCommentController::class, 'index']);
    Route::post('tasks/{task}/comments', [TaskCommentController::class, 'store']);
    Route::delete('task-comments/{comment}', [TaskCommentController::class, 'destroy'])->whereNumber('comment');

    Route::get('tasks/{task}/dependencies', [TaskDependencyController::class, 'index']);
    Route::post('tasks/{task}/dependencies', [TaskDependencyController::class, 'store']);
    Route::delete('task-dependencies/{dependency}', [TaskDependencyController::class, 'destroy'])->whereNumber('dependency');

    Route::get('tasks/{task}/attachments', [TaskAttachmentController::class, 'index']);
    Route::post('tasks/{task}/attachments', [TaskAttachmentController::class, 'store']);
    Route::get('attachments/{attachment}/download', [TaskAttachmentController::class, 'download'])->whereNumber('attachment');
    Route::delete('attachments/{attachment}', [TaskAttachmentController::class, 'destroy'])->whereNumber('attachment');

    Route::get('projects/{project}/csat', [CsatController::class, 'index']);
    Route::post('projects/{project}/csat', [CsatController::class, 'store']);

    Route::get('kaizens', [KaizenController::class, 'index']);
    Route::get('kaizens/leaderboard', [KaizenController::class, 'leaderboard']);
    Route::get('kaizens/badges', [KaizenController::class, 'badges']);
    Route::get('kaizens/reminder-compliance', [KaizenController::class, 'reminderCompliance']);
    Route::post('kaizens', [KaizenController::class, 'store']);
    Route::patch('kaizens/{kaizen}/status', [KaizenController::class, 'updateStatus']);

    Route::get('innovation-ideas', [InnovationIdeaController::class, 'index']);
    Route::post('innovation-ideas', [InnovationIdeaController::class, 'store']);
    Route::patch('innovation-ideas/{idea}/status', [InnovationIdeaController::class, 'updateStatus']);

    Route::get('evaluations', [EvaluationController::class, 'index']);
    Route::get('evaluations/{evaluation}', [EvaluationController::class, 'show']);
    Route::post('evaluations', [EvaluationController::class, 'store']);
    Route::put('evaluations/{evaluation}', [EvaluationController::class, 'update']);
    Route::get('evaluations/{evaluation}/export-pdf', [EvaluationController::class, 'exportPdf']);
    Route::post('evaluations/{evaluation}/peers', [EvaluationPeerController::class, 'store']);
    Route::delete('evaluation-peers/{peer}', [EvaluationPeerController::class, 'destroy']);
});
