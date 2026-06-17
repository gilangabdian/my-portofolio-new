<?php
namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $query = Project::query();

        // Fitur Filter: Jika frontend mengirim ?featured=1, ambil yang featured saja
        if ($request->has('featured') && $request->featured == '1') {
            $query->where('is_featured', true);
        }

        // PERBAIKAN DI SINI: Tambahkan with('skills') agar relasi skill ikut terambil
        $projects = $query->with('skills')->latest()->get();

        return response()->json([
            'success' => true,
            'data' => $projects,
        ]);
    }
    public function show($id)
    {
        $project = Project::with('skills')->findOrFail($id);
        return response()->json($project);
    }

    public function store(StoreProjectRequest $request)
    {
        $data = $request->validated();

        if ($request->hasFile('thumbnail')) {
            if (config('filesystems.default') === 'cloudinary') {
                $data['thumbnail_path'] = $request->file('thumbnail')->storeOnCloudinary('projects')->getSecurePath();
            } else {
                $data['thumbnail_path'] = $request->file('thumbnail')->store('projects', 'public');
            }
        }

        $project = Project::create($data);

        if ($request->has('tech_stack_ids')) {
            $project->skills()->sync($request->tech_stack_ids);
        }

        return response()->json(['message' => 'Project created', 'data' => $project->load('skills')], 201);
    }

    public function update(UpdateProjectRequest $request, $id)
    {
        $project = Project::findOrFail($id);
        $data = $request->validated();

        if ($request->hasFile('thumbnail')) {
            if ($project->thumbnail_path && !str_starts_with($project->thumbnail_path, 'http')) {
                Storage::disk('public')->delete($project->thumbnail_path);
            }
            if (config('filesystems.default') === 'cloudinary') {
                $data['thumbnail_path'] = $request->file('thumbnail')->storeOnCloudinary('projects')->getSecurePath();
            } else {
                $data['thumbnail_path'] = $request->file('thumbnail')->store('projects', 'public');
            }
        }

        $project->update([
            'title' => $data['title'],
            'description' => $data['description'],
            'is_featured' => $data['is_featured'] ?? false,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'status' => $data['status'],
            'type' => $data['type'] ?? null,
            'team_size' => $data['team_size'] ?? null,
            'role' => $data['role'] ?? null,
            'live_demo_link' => $data['live_demo_link'] ?? null,
            'repository_link' => $data['repository_link'] ?? null,
        ]);

        if (isset($data['thumbnail_path'])) {
            $project->update(['thumbnail_path' => $data['thumbnail_path']]);
        }

        if ($request->has('tech_stack_ids')) {
            $project->skills()->sync($request->tech_stack_ids);
        }

        return response()->json(['message' => 'Project updated', 'data' => $project->load('skills')]);
    }

    public function destroy($id)
    {
        $project = Project::findOrFail($id);
        if ($project->thumbnail_path && !str_starts_with($project->thumbnail_path, 'http')) {
            Storage::disk('public')->delete($project->thumbnail_path);
        }
        $project->delete();
        return response()->json(['message' => 'Project deleted']);
    }
}
