<?php

namespace Tests\Feature;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
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
