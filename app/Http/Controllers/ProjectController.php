<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $projects = $user->isAdmin()
            ? Project::with(['owner', 'members'])->withCount('tasks')->latest()->get()
            : Project::with(['owner', 'members'])
                ->withCount('tasks')
                ->where(function ($query) use ($user) {
                    $query->where('owner_id', $user->id)
                        ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id));
                })
                ->latest()
                ->get();

        return response()->json(['projects' => $projects]);
    }

    public function store(Request $request): JsonResponse
    {
        // Only admins can create projects
        if (! $request->user()->isAdmin()) {
            return response()->json(['message' => 'Only admins can create projects'], 403);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $project = Project::create([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'owner_id' => $request->user()->id,
            'status' => 'active',
        ]);

        if (! empty($data['member_ids'])) {
            $project->members()->sync($data['member_ids']);
        }

        return response()->json([
            'project' => $project->load(['owner', 'members']),
        ], 201);
    }

    public function show(Request $request, Project $project): JsonResponse
    {
        if (! $project->hasAccess($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'project' => $project->load(['owner', 'members', 'tasks.assignee', 'tasks.creator']),
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && $project->owner_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', Rule::in(['active', 'archived'])],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ]);

        $project->update(collect($data)->except('member_ids')->toArray());

        if (array_key_exists('member_ids', $data)) {
            $project->members()->sync($data['member_ids'] ?? []);
        }

        return response()->json([
            'project' => $project->load(['owner', 'members']),
        ]);
    }

    public function destroy(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();
        if (! $user->isAdmin() && $project->owner_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $project->delete();
        return response()->json(['message' => 'Project deleted']);
    }

    /**
     * List all members eligible to be added to a project (used by frontend create/edit forms).
     */
    public function availableMembers(): JsonResponse
    {
        return response()->json([
            'users' => User::select('id', 'name', 'email', 'role')->orderBy('name')->get(),
        ]);
    }
}
