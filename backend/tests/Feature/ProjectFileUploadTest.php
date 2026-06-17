<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProjectFileUploadTest extends TestCase
{
    use RefreshDatabase;

    // Helper function agar kita tidak mengulang-ulang setup
    protected function setUp(): void
    {
        parent::setUp();

        // 1. Kita paksa aplikasi menganggap Default Disk adalah 'public'
        //    Ini mensimulasikan kondisi environment Lokal / Testing
        Config::set('filesystems.default', 'public');

        // 2. Kita palsukan disk 'public'
        Storage::fake('public');
    }

    public function test_project_upload_stores_file_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('project-thumb.jpg');

        $response = $this->postJson('/api/projects', [
            'title' => 'Test Project',
            'description' => 'Desc',
            'thumbnail' => $file,
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-01',
            'status' => 'completed',
        ]);

        $response->assertStatus(201);

        $project = Project::first();

        // Assert file ada di disk public (yang sudah dipalsukan)
        $this->assertNotNull($project->thumbnail_path);
        Storage::disk('public')->assertExists($project->thumbnail_path);
    }

    public function test_updating_project_image_replaces_old_file()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // 1. Upload awal
        $oldFile = UploadedFile::fake()->image('old.jpg');
        $this->postJson('/api/projects', [
            'title' => 'Original',
            'description' => 'Desc',
            'thumbnail' => $oldFile,
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-01',
            'status' => 'completed',
        ]);

        $project = Project::first();
        $oldPath = $project->thumbnail_path;
        Storage::disk('public')->assertExists($oldPath);

        // 2. Update
        $newFile = UploadedFile::fake()->image('new.jpg');
        $this->putJson("/api/projects/{$project->id}", [
            'title' => 'Updated',
            'description' => 'Desc',
            'thumbnail' => $newFile,
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-01',
            'status' => 'completed',
        ]);

        $project->refresh();
        $newPath = $project->thumbnail_path;

        // Cek file lama hilang, file baru ada
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($newPath);
    }

    public function test_deleting_project_removes_file()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('delete.jpg');
        $this->postJson('/api/projects', [
            'title' => 'To Delete',
            'description' => 'Desc',
            'thumbnail' => $file,
            'start_date' => '2025-01-01',
            'end_date' => '2025-06-01',
            'status' => 'completed',
        ]);

        $project = Project::first();
        $path = $project->thumbnail_path;

        $this->deleteJson("/api/projects/{$project->id}");

        Storage::disk('public')->assertMissing($path);
    }
}
