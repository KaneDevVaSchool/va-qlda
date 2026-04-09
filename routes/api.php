<?php

use App\Http\Controllers\Api\ActivityFeedController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContractApprovalController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\ContractFileController;
use App\Http\Controllers\Api\ContractLookupController;
use App\Http\Controllers\Api\ContractPaymentController;
use App\Http\Controllers\Api\CsatController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DepartmentController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\EvaluationPeerController;
use App\Http\Controllers\Api\InnovationIdeaController;
use App\Http\Controllers\Api\KaizenController;
use App\Http\Controllers\Api\KpiController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\ProjectDocumentController;
use App\Http\Controllers\Api\ProjectImportController;
use App\Http\Controllers\Api\ProjectPhaseController;
use App\Http\Controllers\Api\ProjectSupplyController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\TaskAttachmentController;
use App\Http\Controllers\Api\TaskBulkController;
use App\Http\Controllers\Api\TaskCommentController;
use App\Http\Controllers\Api\TaskController;
use App\Http\Controllers\Api\TaskDependencyController;
use App\Http\Controllers\Api\TeamController;
use App\Http\Controllers\Api\UserActivityLogController;
use App\Http\Controllers\Api\UserLookupController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\UserRbacController;
use App\Http\Controllers\Api\UserSecurityController;
use App\Http\Controllers\Api\UserSessionController;
use App\Http\Controllers\Auth\GoogleOAuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::post('/login/mfa', [AuthController::class, 'loginMfa'])->middleware('throttle:login');

Route::get('/auth/google/config', [GoogleOAuthController::class, 'config']);
Route::get('/auth/google/redirect', [GoogleOAuthController::class, 'redirectUrl'])->middleware('throttle:google_oauth');
Route::post('/auth/google/exchange', [GoogleOAuthController::class, 'exchange'])->middleware('throttle:google_oauth');

Route::middleware(['auth:sanctum', 'touch.session'])->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/me/profile', [UserProfileController::class, 'show']);
    Route::patch('/me/profile', [UserProfileController::class, 'update']);
    Route::post('/me/profile/avatar', [UserProfileController::class, 'avatar']);

    Route::get('/me/security', [UserSecurityController::class, 'show']);
    Route::put('/me/security/password', [UserSecurityController::class, 'password']);

    Route::get('/me/sessions', [UserSessionController::class, 'index']);
    Route::delete('/me/sessions/{session}', [UserSessionController::class, 'destroy'])->whereNumber('session');
    Route::post('/me/sessions/revoke-others', [UserSessionController::class, 'revokeOthers']);

    Route::get('/me/rbac', [UserRbacController::class, 'show']);
    Route::patch('/me/rbac', [UserRbacController::class, 'update']);

    Route::get('/me/activity', [UserActivityLogController::class, 'index']);
    Route::get('/me/activity/export.csv', [UserActivityLogController::class, 'exportCsv']);

    Route::get('/users/lookup', [UserLookupController::class, 'index']);
    Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

    Route::get('/activity-feed/unread-count', [ActivityFeedController::class, 'unreadCount']);
    Route::post('/activity-feed/read-all', [ActivityFeedController::class, 'markAllRead']);
    Route::patch('/activity-feed/{auditLog}/read', [ActivityFeedController::class, 'markRead']);
    Route::get('/activity-feed', [ActivityFeedController::class, 'index']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    Route::get('/teams', [TeamController::class, 'index']);
    Route::post('/teams', [TeamController::class, 'store']);
    Route::get('/teams/{team}', [TeamController::class, 'show']);
    Route::patch('/teams/{team}', [TeamController::class, 'update']);
    Route::delete('/teams/{team}', [TeamController::class, 'destroy']);
    Route::post('/teams/{team}/members', [TeamController::class, 'addMembers']);
    Route::patch('/teams/{team}/members/{userId}', [TeamController::class, 'updateMember'])->whereNumber('userId');
    Route::delete('/teams/{team}/members/{userId}', [TeamController::class, 'removeMember'])->whereNumber('userId');

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
    Route::get('projects/{project}/activities', [ProjectController::class, 'activities']);
    Route::post('projects/{project}/join', [ProjectController::class, 'joinStakeholder']);
    Route::get('projects/{project}/phases', [ProjectPhaseController::class, 'index']);
    Route::post('projects/{project}/phases', [ProjectPhaseController::class, 'store']);
    Route::put('project-phases/{phase}', [ProjectPhaseController::class, 'update'])->whereNumber('phase');
    Route::delete('project-phases/{phase}', [ProjectPhaseController::class, 'destroy'])->whereNumber('phase');
    Route::get('projects/{project}/supplies', [ProjectSupplyController::class, 'index']);
    Route::post('projects/{project}/supplies', [ProjectSupplyController::class, 'store']);
    Route::put('project-supplies/{supply}', [ProjectSupplyController::class, 'update'])->whereNumber('supply');
    Route::delete('project-supplies/{supply}', [ProjectSupplyController::class, 'destroy'])->whereNumber('supply');
    Route::apiResource('projects', ProjectController::class);
    Route::get('projects/{project}/gantt', [ProjectController::class, 'gantt']);
    Route::get('projects/{project}/attachments', [ProjectController::class, 'attachments']);
    Route::get('projects/{project}/media', [ProjectController::class, 'media']);
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
    Route::post('projects/{project}/tasks/bulk-create', [TaskController::class, 'bulkStore']);
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

    Route::get('contract-lookups', ContractLookupController::class);
    Route::post('departments', [DepartmentController::class, 'store']);
    Route::get('contracts/upcoming-payments', [ContractPaymentController::class, 'upcoming']);
    Route::get('contracts/export.csv', [ContractController::class, 'exportCsv']);
    Route::get('contracts/trash', [ContractController::class, 'trashIndex']);
    Route::post('contracts/{id}/restore', [ContractController::class, 'restore'])->whereNumber('id');
    Route::delete('contracts/{id}/force', [ContractController::class, 'forceDestroy'])->whereNumber('id');
    Route::get('contracts/{contract}/summary.pdf', [ContractController::class, 'summaryPdf'])->whereNumber('contract');
    Route::get('contracts/{contract}/logs/actions', [ContractController::class, 'logActions'])->whereNumber('contract');
    Route::apiResource('contracts', ContractController::class)->whereNumber('contract');
    Route::get('contracts/{contract}/logs', [ContractController::class, 'logs'])->whereNumber('contract');
    Route::post('contracts/{contract}/submit', [ContractController::class, 'submit'])->whereNumber('contract');
    Route::post('contracts/{contract}/terminate', [ContractController::class, 'terminate'])->whereNumber('contract');
    Route::post('contracts/{contract}/approve', [ContractApprovalController::class, 'approve'])->whereNumber('contract');
    Route::post('contracts/{contract}/reject', [ContractApprovalController::class, 'reject'])->whereNumber('contract');
    Route::get('contracts/{contract}/files', [ContractFileController::class, 'index'])->whereNumber('contract');
    Route::post('contracts/{contract}/files', [ContractFileController::class, 'store'])->whereNumber('contract');
    Route::get('contracts/{contract}/files/{file}/download', [ContractFileController::class, 'download'])
        ->whereNumber('contract')
        ->whereNumber('file');
    Route::get('contracts/{contract}/files/{file}/preview', [ContractFileController::class, 'preview'])
        ->whereNumber('contract')
        ->whereNumber('file');
    Route::get('contracts/{contract}/payments', [ContractPaymentController::class, 'index'])->whereNumber('contract');
    Route::post('contracts/{contract}/payments/{payment}/mark-paid', [ContractPaymentController::class, 'markPaid'])
        ->whereNumber('contract')
        ->whereNumber('payment');
});
