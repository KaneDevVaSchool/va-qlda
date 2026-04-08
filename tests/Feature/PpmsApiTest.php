<?php

namespace Tests\Feature;

use App\Models\LoginHistory;
use App\Models\User;
use App\Services\ProjectImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\Support\FixedCodeLoginMfaService;
use Tests\TestCase;

class PpmsApiTest extends TestCase
{
    use RefreshDatabase;

    private User $pmUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pmUser = User::factory()->create([
            'email' => 'pm-test@va-schools.vn',
            'password' => 'password',
            'role' => 'pm',
        ]);
    }

    public function test_google_auth_config_returns_disabled_without_credentials(): void
    {
        Config::set('services.google.client_id', '');
        Config::set('services.google.client_secret', '');

        $this->getJson('/api/auth/google/config')
            ->assertOk()
            ->assertJson(['enabled' => false]);
    }

    public function test_google_exchange_rejects_unknown_key(): void
    {
        $this->postJson('/api/auth/google/exchange', [
            'exchange' => str_repeat('a', 48),
        ])->assertUnprocessable();
    }

    public function test_login_returns_token_and_records_success_history(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'pm-test@va-schools.vn',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $this->pmUser->id,
            'event' => LoginHistory::EVENT_LOGIN_SUCCESS,
        ]);
    }

    public function test_login_fails_with_invalid_password_and_logs_failed_when_user_exists(): void
    {
        $response = $this->postJson('/api/login', [
            'email' => 'pm-test@va-schools.vn',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422);

        $this->assertDatabaseHas('login_histories', [
            'user_id' => $this->pmUser->id,
            'event' => LoginHistory::EVENT_LOGIN_FAILED,
        ]);
    }

    public function test_login_locks_account_after_threshold_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/login', [
                'email' => 'pm-test@va-schools.vn',
                'password' => 'wrong-password',
            ])->assertStatus(422);
        }

        $this->pmUser->refresh();
        $this->assertNotNull($this->pmUser->locked_until);

        $this->postJson('/api/login', [
            'email' => 'pm-test@va-schools.vn',
            'password' => 'password',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_login_mfa_flow_with_email_otp_when_enabled(): void
    {
        $this->app->bind(\App\Services\LoginMfaService::class, FixedCodeLoginMfaService::class);
        Config::set('ppms.login_mfa_enabled', true);
        Config::set('ppms.login_mfa_roles', ['pm']);

        $step1 = $this->postJson('/api/login', [
            'email' => 'pm-test@va-schools.vn',
            'password' => 'password',
            'remember' => true,
        ]);

        $step1->assertOk()
            ->assertJsonPath('mfa_required', true)
            ->assertJsonStructure(['challenge', 'expires_in']);

        $challenge = $step1->json('challenge');
        $this->assertIsString($challenge);
        $this->assertSame(48, strlen($challenge));

        $this->postJson('/api/login/mfa', [
            'challenge' => $challenge,
            'code' => '444444',
            'remember' => true,
        ])
            ->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_projects_create_requires_authentication(): void
    {
        $this->postJson('/api/projects', [
            'name' => 'X',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->assertUnauthorized();
    }

    public function test_project_create_and_show_with_sanctum(): void
    {
        Sanctum::actingAs($this->pmUser);

        $create = $this->postJson('/api/projects', [
            'name' => 'Dự án kiểm thử API',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ]);

        $create->assertCreated();
        $id = $create->json('id');
        $this->assertNotNull($id);

        $this->getJson("/api/projects/{$id}")->assertOk()->assertJsonPath('name', 'Dự án kiểm thử API');
    }

    public function test_developer_can_view_but_not_update_project(): void
    {
        Sanctum::actingAs($this->pmUser);

        $id = $this->postJson('/api/projects', [
            'name' => 'Policy view test',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->assertCreated()->json('id');

        $developer = User::factory()->create(['role' => 'developer']);
        Sanctum::actingAs($developer);

        $this->getJson("/api/projects/{$id}")->assertOk()->assertJsonPath('name', 'Policy view test');

        $this->putJson("/api/projects/{$id}", [
            'name' => 'Hacked',
        ])->assertForbidden();
    }

    public function test_projects_bulk_updates_phase_with_audit(): void
    {
        Sanctum::actingAs($this->pmUser);

        $projectId = $this->postJson('/api/projects', [
            'name' => 'Bulk phase test',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->assertCreated()->json('id');

        $this->postJson('/api/projects/bulk', [
            'project_ids' => [$projectId],
            'phase' => 'uat',
        ])->assertOk()->assertJsonPath('updated', 1);

        $this->getJson("/api/projects/{$projectId}")->assertOk()->assertJsonPath('phase', 'uat');

        $this->assertDatabaseHas('audit_logs', [
            'auditable_type' => \App\Models\Project::class,
            'auditable_id' => $projectId,
            'action' => 'project.bulk_updated',
        ]);
    }

    public function test_projects_filtered_csv_requires_role(): void
    {
        Sanctum::actingAs($this->pmUser);

        $this->get('/api/reports/export/projects-filtered.csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_projects_filtered_json_export_ok(): void
    {
        Sanctum::actingAs($this->pmUser);

        $res = $this->get('/api/reports/export/projects-filtered.json');
        $res->assertOk();
        $this->assertStringContainsString('application/json', (string) $res->headers->get('content-type'));
        $body = $res->streamedContent();
        $this->assertStringContainsString('"export_version"', $body);
        $this->assertStringContainsString('"projects":', $body);
    }

    public function test_projects_import_json_preview_ok(): void
    {
        Sanctum::actingAs($this->pmUser);

        $payload = [
            'projects' => [
                [
                    'name' => 'JSON import row',
                    'type' => 'delivery',
                    'owner_id' => (string) $this->pmUser->id,
                ],
            ],
        ];
        $file = UploadedFile::fake()->createWithContent('rows.json', json_encode($payload));
        $this->post('/api/projects/import/preview', ['file' => $file])
            ->assertOk()
            ->assertJsonPath('summary.valid', 1);
    }

    public function test_projects_import_template_ok_for_pm(): void
    {
        Sanctum::actingAs($this->pmUser);

        $this->get('/api/reports/import/projects-template.csv')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_projects_import_template_forbidden_for_tl(): void
    {
        $tl = User::factory()->create(['role' => 'tl']);
        Sanctum::actingAs($tl);

        $this->get('/api/reports/import/projects-template.csv')->assertForbidden();
    }

    public function test_project_label_suggestions_returns_json(): void
    {
        Sanctum::actingAs($this->pmUser);

        $pid = $this->postJson('/api/projects', [
            'name' => 'Labeled',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
            'labels' => ['Priority', 'Q1'],
        ])->assertCreated()->json('id');

        $this->assertNotNull($pid);

        $this->getJson('/api/projects/label-suggestions')
            ->assertOk()
            ->assertJsonFragment(['labels' => ['Priority', 'Q1']]);
    }

    public function test_projects_list_filters_by_label_query(): void
    {
        Sanctum::actingAs($this->pmUser);

        $this->postJson('/api/projects', [
            'name' => 'Has VIP',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
            'labels' => ['VIP'],
        ])->assertCreated();

        $this->postJson('/api/projects', [
            'name' => 'No tag',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->assertCreated();

        $res = $this->getJson('/api/projects?label='.rawurlencode('VIP'));
        $res->assertOk();
        $data = $res->json('data');
        $this->assertIsArray($data);
        $this->assertGreaterThanOrEqual(1, count($data));
        $this->assertTrue(collect($data)->every(fn ($p) => in_array('VIP', $p['labels'] ?? [], true)));
    }

    public function test_projects_bulk_add_labels(): void
    {
        Sanctum::actingAs($this->pmUser);

        $id = $this->postJson('/api/projects', [
            'name' => 'Bulk label me',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->assertCreated()->json('id');

        $this->postJson('/api/projects/bulk', [
            'project_ids' => [$id],
            'add_labels' => ['Urgent'],
        ])->assertOk()->assertJsonPath('updated', 1);

        $this->getJson("/api/projects/{$id}")->assertOk()->assertJsonPath('labels', ['Urgent']);
    }

    public function test_projects_bulk_destroy_deletes_for_pm_and_forbids_tl(): void
    {
        Sanctum::actingAs($this->pmUser);

        $id = $this->postJson('/api/projects', [
            'name' => 'To delete bulk',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->assertCreated()->json('id');

        $this->postJson('/api/projects/bulk-destroy', [
            'project_ids' => [$id],
        ])->assertOk()->assertJsonPath('deleted', 1);

        $this->assertDatabaseMissing('projects', ['id' => $id]);

        $id2 = $this->postJson('/api/projects', [
            'name' => 'TL cannot bulk delete',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->assertCreated()->json('id');

        $tl = User::factory()->create(['role' => 'tl']);
        Sanctum::actingAs($tl);

        $this->postJson('/api/projects/bulk-destroy', [
            'project_ids' => [$id2],
        ])->assertForbidden();

        $this->assertDatabaseHas('projects', ['id' => $id2]);
    }

    public function test_projects_import_preview_and_commit_creates_project(): void
    {
        Sanctum::actingAs($this->pmUser);

        $buf = fopen('php://memory', 'r+');
        fputcsv($buf, ProjectImportService::TEMPLATE_HEADERS);
        fputcsv($buf, [
            '',
            'CSV Import Project',
            '',
            '',
            'delivery',
            'planning',
            'on_track',
            '0',
            '',
            '',
            (string) $this->pmUser->id,
            '',
            '',
            'Mô tả',
        ]);
        rewind($buf);
        $content = stream_get_contents($buf) ?: '';
        fclose($buf);
        $file = UploadedFile::fake()->createWithContent('import.csv', $content);

        $preview = $this->post('/api/projects/import/preview', ['file' => $file])->assertOk();
        $previewId = $preview->json('preview_id');
        $this->assertNotEmpty($previewId);
        $this->assertSame(1, $preview->json('summary.valid'));

        $this->postJson('/api/projects/import/commit', ['preview_id' => $previewId])
            ->assertOk()
            ->assertJsonPath('created', 1)
            ->assertJsonPath('updated', 0);

        $this->assertDatabaseHas('projects', ['name' => 'CSV Import Project']);
    }

    public function test_task_create_on_project(): void
    {
        Sanctum::actingAs($this->pmUser);

        $projectId = $this->postJson('/api/projects', [
            'name' => 'P Task Test',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->json('id');

        $task = $this->postJson("/api/projects/{$projectId}/tasks", [
            'name' => 'Task A',
            'estimate_hours' => 4,
            'complexity' => 3,
            'impact' => 3,
        ]);

        $task->assertCreated()->assertJsonPath('name', 'Task A');
    }

    public function test_project_documents_crud(): void
    {
        Sanctum::actingAs($this->pmUser);
        Storage::fake('public');

        $projectId = $this->postJson('/api/projects', [
            'name' => 'P Docs Test',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->json('id');

        $folder = $this->postJson("/api/projects/{$projectId}/documents", [
            'doc_type' => 'folder',
            'name' => 'Hồ sơ',
        ])->assertCreated();

        $folderId = $folder->json('id');

        $this->postJson("/api/projects/{$projectId}/documents", [
            'doc_type' => 'link',
            'name' => 'Tham chiếu web',
            'url' => 'https://example.com/spec',
            'parent_id' => $folderId,
        ])->assertCreated();

        $this->getJson("/api/projects/{$projectId}/documents")
            ->assertOk()
            ->assertJsonCount(2);

        $file = UploadedFile::fake()->create('spec.pdf', 80, 'application/pdf');
        $up = $this->post("/api/projects/{$projectId}/documents/upload", [
            'file' => $file,
            'parent_id' => $folderId,
        ])->assertCreated();

        $docId = $up->json('id');
        $this->getJson("/api/project-documents/{$docId}/download")->assertOk();

        $this->deleteJson("/api/project-documents/{$folderId}")->assertNoContent();
        $this->getJson("/api/projects/{$projectId}/documents")->assertOk()->assertJsonCount(0);
    }

    public function test_project_lists_all_task_attachments(): void
    {
        Sanctum::actingAs($this->pmUser);
        Storage::fake('public');

        $projectId = $this->postJson('/api/projects', [
            'name' => 'P Attach Test',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->json('id');

        $taskId = $this->postJson("/api/projects/{$projectId}/tasks", [
            'name' => 'Task With File',
            'estimate_hours' => 1,
            'complexity' => 2,
            'impact' => 2,
        ])->json('id');

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $this->post("/api/tasks/{$taskId}/attachments", ['file' => $file])
            ->assertCreated();

        $this->getJson("/api/projects/{$projectId}/attachments")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.original_name', 'doc.pdf')
            ->assertJsonPath('0.task.name', 'Task With File');
    }

    public function test_project_media_unifies_documents_and_task_attachments(): void
    {
        Sanctum::actingAs($this->pmUser);
        Storage::fake('public');

        $projectId = $this->postJson('/api/projects', [
            'name' => 'P Media Test',
            'type' => 'delivery',
            'owner_id' => $this->pmUser->id,
        ])->json('id');

        $this->postJson("/api/projects/{$projectId}/documents", [
            'doc_type' => 'folder',
            'name' => 'Docs',
        ])->assertCreated();

        $taskId = $this->postJson("/api/projects/{$projectId}/tasks", [
            'name' => 'Task With File',
            'estimate_hours' => 1,
            'complexity' => 2,
            'impact' => 2,
        ])->json('id');

        $file = UploadedFile::fake()->create('doc.pdf', 50, 'application/pdf');
        $this->post("/api/tasks/{$taskId}/attachments", ['file' => $file])
            ->assertCreated();

        $this->getJson("/api/projects/{$projectId}/media")
            ->assertOk()
            ->assertJsonCount(2);

        $this->getJson("/api/projects/{$projectId}/media?scope=project")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.scope', 'project');

        $this->getJson("/api/projects/{$projectId}/media?scope=task")
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.scope', 'task');
    }

    public function test_kpi_current_requires_authentication(): void
    {
        $this->getJson('/api/kpi/current')->assertUnauthorized();
    }

    public function test_kpi_current_returns_json_for_authenticated_user(): void
    {
        Sanctum::actingAs($this->pmUser);

        $this->getJson('/api/kpi/current')
            ->assertOk()
            ->assertJsonStructure(['person']);
    }
}
