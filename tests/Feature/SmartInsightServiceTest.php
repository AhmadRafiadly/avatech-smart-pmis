<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Project;
use App\Models\ProjectModule;
use App\Models\ProjectQcTest;
use App\Models\TeamAssignment;
use App\Models\User;
use App\Services\SmartInsightService;
use Tests\TestCase;

class SmartInsightServiceTest extends TestCase
{
    public function test_team_badge_and_member_outputs_share_the_active_team_definition(): void
    {
        $project = $this->project('Development');
        $lead = User::factory()->create();
        $taskPic = User::factory()->create();
        $completed = User::factory()->create();
        $archived = User::factory()->create(['archived_at' => now()]);
        $project->update(['lead_user_id' => $lead->id]);
        $project->tasks()->create(['title' => 'PIC only', 'status' => 'planned', 'priority' => 'medium', 'assigned_to' => $taskPic->id]);
        $this->assign($project, $completed, 'completed');
        $this->assign($project, $archived, 'in_progress');

        $service = app(SmartInsightService::class);
        $this->assertContains('Belum Ada Tim', $this->labels($service, $project));
        $this->assertSame([], $service->projectAvatars($project));
        $this->assertSame(0, $service->projectAssignmentCount($project));
        $this->assertSame([], $service->projectHiddenMemberNames($project));

        $planned = User::factory()->create(['name' => 'Anggota Planned']);
        $active = User::factory()->create(['name' => 'Anggota Aktif']);
        $this->assign($project, $planned, 'planned');
        $this->assign($project, $active, 'in_progress');

        $this->assertNotContains('Belum Ada Tim', $this->labels($service, $project));
        $this->assertSame(2, $service->projectAssignmentCount($project));
        $this->assertSame(['Anggota Planned'], array_column($service->projectAvatars($project, 1), 'name'));
        $this->assertSame(['Anggota Aktif'], $service->projectHiddenMemberNames($project, 1));
    }

    public function test_generic_qc_badge_is_phase_aware(): void
    {
        $service = app(SmartInsightService::class);

        foreach (['Gathering', 'Planning', 'Design'] as $phase) {
            $project = $this->project($phase);
            ProjectModule::create(['project_id' => $project->id, 'title' => 'Modul']);
            $this->assertNotContains('Perlu QC', $this->labels($service, $project));
        }

        $developmentWithoutModule = $this->project('Development');
        $this->assertNotContains('Perlu QC', $this->labels($service, $developmentWithoutModule));

        $development = $this->project('Development');
        ProjectModule::create(['project_id' => $development->id, 'title' => 'Modul']);
        $this->assertContains('Perlu QC', $this->labels($service, $development));

        $this->qc($development, 'passed');
        $this->assertNotContains('Perlu QC', $this->labels($service, $development));

        $qc = $this->project('QC');
        $this->qc($qc, 'pending');
        $this->assertContains('Perlu QC', $this->labels($service, $qc));
    }

    public function test_failed_or_retest_qc_warning_stays_visible_and_outranks_generic_qc(): void
    {
        $service = app(SmartInsightService::class);

        foreach (['Gathering', 'Development', 'QC'] as $phase) {
            $project = $this->project($phase);
            ProjectModule::create(['project_id' => $project->id, 'title' => 'Modul']);
            $this->qc($project, $phase === 'QC' ? 'retest' : 'failed');
            $labels = $this->labels($service, $project);

            $this->assertContains('QC Gagal / Perlu Retest', $labels);
            $this->assertNotContains('Perlu QC', $labels);
        }
    }

    private function labels(SmartInsightService $service, Project $project): array
    {
        return array_column($service->projectBadges($project), 'label');
    }

    private function project(string $phase): Project
    {
        $client = Client::create(['name' => 'Client '.uniqid(), 'code' => strtoupper(substr(uniqid(), -4)), 'tier' => 'standard']);

        return Project::create([
            'code' => strtoupper(substr(uniqid(), -4)),
            'name' => 'Project '.uniqid(),
            'client_id' => $client->id,
            'phase' => $phase,
            'progress' => 0,
            'status' => 'attention',
        ]);
    }

    private function assign(Project $project, User $user, string $status): void
    {
        TeamAssignment::create([
            'project_id' => $project->id,
            'user_id' => $user->id,
            'title' => 'Developer',
            'type' => 'project',
            'status' => $status,
        ]);
    }

    private function qc(Project $project, string $status): void
    {
        ProjectQcTest::create([
            'project_id' => $project->id,
            'title' => 'Test case',
            'scenario' => 'Jalankan skenario',
            'status' => $status,
            'priority' => 'medium',
        ]);
    }
}
