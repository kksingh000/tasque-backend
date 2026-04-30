<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    /**
     * List tasks for a specific project.
     */
    public function index(Request $request, Project $project): JsonResponse
    {
        if (! $project->hasAccess($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $tasks = $project->tasks()
            ->with(['assignee:id,name,email', 'creator:id,name,email'])
            ->latest()
            ->get();

        return response()->json(['tasks' => $tasks]);
    }

    public function store(Request $request, Project $project): JsonResponse
    {
        $user = $request->user();
        if (! $project->hasAccess($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        // Only admins or project owner can create tasks
        if (! $user->isAdmin() && $project->owner_id !== $user->id) {
            return response()->json(['message' => 'Only admins or project owners can create tasks'], 403);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', Rule::in(['todo', 'in_progress', 'done'])],
            'priority' => ['nullable', Rule::in(['low', 'medium', 'high'])],
            'due_date' => ['nullable', 'date'],
        ]);

        $task = $project->tasks()->create([
            ...$data,
            'created_by' => $user->id,
            'status' => $data['status'] ?? 'todo',
            'priority' => $data['priority'] ?? 'medium',
        ]);

        return response()->json([
            'task' => $task->load(['assignee:id,name,email', 'creator:id,name,email']),
        ], 201);
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        if (! $task->project->hasAccess($request->user())) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response()->json([
            'task' => $task->load(['assignee:id,name,email', 'creator:id,name,email', 'project:id,name']),
        ]);
    }

    public function update(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        $project = $task->project;

        if (! $project->hasAccess($user)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $isPrivileged = $user->isAdmin() || $project->owner_id === $user->id;
        $isAssignee = $task->assigned_to === $user->id;

        // Members who are assignees can ONLY update status
        if (! $isPrivileged) {
            if (! $isAssignee) {
                return response()->json(['message' => 'You can only update tasks assigned to you'], 403);
            }
            $data = $request->validate([
                'status' => ['required', Rule::in(['todo', 'in_progress', 'done'])],
            ]);
            $task->update($data);
        } else {
            $data = $request->validate([
                'title' => ['sometimes', 'string', 'max:255'],
                'description' => ['nullable', 'string'],
                'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
                'status' => ['sometimes', Rule::in(['todo', 'in_progress', 'done'])],
                'priority' => ['sometimes', Rule::in(['low', 'medium', 'high'])],
                'due_date' => ['nullable', 'date'],
            ]);
            $task->update($data);
        }

        return response()->json([
            'task' => $task->fresh()->load(['assignee:id,name,email', 'creator:id,name,email']),
        ]);
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        $user = $request->user();
        $project = $task->project;

        if (! $user->isAdmin() && $project->owner_id !== $user->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $task->delete();
        return response()->json(['message' => 'Task deleted']);
    }
}
