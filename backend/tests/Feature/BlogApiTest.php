<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\User;
use App\Models\Blog;

class BlogApiTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $token;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test-token')->plainTextToken;
    }

    public function test_can_get_public_published_blogs_only()
    {
        Blog::create([
            'title' => 'Public Blog',
            'content' => 'Content',
            'is_published' => true
        ]);
        Blog::create([
            'title' => 'Draft Blog',
            'content' => 'Content',
            'is_published' => false
        ]);

        $response = $this->getJson('/api/blogs');
        
        $response->assertStatus(200)
                 ->assertJsonCount(1)
                 ->assertJsonFragment(['title' => 'Public Blog'])
                 ->assertJsonMissing(['title' => 'Draft Blog']);
    }

    public function test_admin_can_get_all_blogs()
    {
        Blog::create(['title' => 'Public Blog', 'content' => 'Content', 'is_published' => true]);
        Blog::create(['title' => 'Draft Blog', 'content' => 'Content', 'is_published' => false]);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->getJson('/api/admin/blogs');
        
        $response->assertStatus(200)
                 ->assertJsonCount(2);
    }

    public function test_can_get_public_single_blog_by_slug()
    {
        $blog = Blog::create([
            'title' => 'My First Blog',
            'content' => 'Hello World',
            'is_published' => true
        ]);

        $response = $this->getJson('/api/blogs/' . $blog->slug);
        
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'My First Blog']);
    }

    public function test_cannot_get_draft_single_blog_publicly()
    {
        $blog = Blog::create([
            'title' => 'My Draft',
            'content' => 'Hello World',
            'is_published' => false
        ]);

        $response = $this->getJson('/api/blogs/' . $blog->slug);
        
        $response->assertStatus(404);
    }

    public function test_admin_can_create_blog_and_calculates_read_time_and_slug_correctly()
    {
        // 400 words
        $content = str_repeat("Word ", 400);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/blogs', [
                             'title' => 'Test Auto Slug',
                             'content' => $content,
                             'is_published' => true
                         ]);
        
        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'title' => 'Test Auto Slug',
                     'slug' => 'test-auto-slug',
                     'read_time' => 2 // 400 words / 200 words per min = 2
                 ]);
    }

    public function test_unique_slug_generation_on_duplicate_title()
    {
        Blog::create(['title' => 'Duplicate Title', 'content' => 'Content']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/blogs', [
                             'title' => 'Duplicate Title',
                             'content' => 'Other Content',
                         ]);
        
        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'slug' => 'duplicate-title-1'
                 ]);
    }

    public function test_admin_can_update_blog()
    {
        $blog = Blog::create(['title' => 'Old Title', 'content' => 'Old Content']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->putJson('/api/blogs/' . $blog->id, [
                             'title' => 'New Title',
                             'content' => 'New Content',
                         ]);
        
        $response->assertStatus(200)
                 ->assertJsonFragment(['title' => 'New Title', 'slug' => 'new-title']);
    }

    public function test_admin_can_delete_blog()
    {
        $blog = Blog::create(['title' => 'To Delete', 'content' => 'Content']);

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->deleteJson('/api/blogs/' . $blog->id);
        
        $response->assertStatus(200);
        $this->assertDatabaseMissing('blogs', ['id' => $blog->id]);
    }

    public function test_admin_can_upload_inline_image_locally()
    {
        Storage::fake('public');
        config(['filesystems.default' => 'local']);

        $file = UploadedFile::fake()->image('test_inline.jpg');

        $response = $this->withHeader('Authorization', 'Bearer ' . $this->token)
                         ->postJson('/api/blogs/upload-image', [
                             'image' => $file
                         ]);
        
        $response->assertStatus(200)
                 ->assertJsonStructure(['url']);

        // Check file exists in folder
        $files = Storage::disk('public')->files('blogs_inline');
        $this->assertCount(1, $files);
    }
}
