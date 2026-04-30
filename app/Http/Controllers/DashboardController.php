<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Build the base task query scoped to what the user can see
        if ($user->isAdmin()) {
            $taskQuery = Task::query();
            $projectQuery = Project::query();
        } else {
            $accessibleProjectIds = Project::where('owner_id', $user->id)
                ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
                ->pluck('id');

            $taskQuery = Task::whereIn('project_id', $accessibleProjectIds);
            $projectQuery = Project::whereIn('id', $accessibleProjectIds);
        }

        $stats = [
            'total_projects' => (clone $projectQuery)->count(),
            'active_projects' => (clone $projectQuery)->where('status', 'active')->count(),
            'total_tasks' => (clone $taskQuery)->count(),
            'todo_tasks' => (clone $taskQuery)->where('status', 'todo')->count(),
            'in_progress_tasks' => (clone $taskQuery)->where('status', 'in_progress')->count(),
            'done_tasks' => (clone $taskQuery)->where('status', 'done')->count(),
            'overdue_tasks' => (clone $taskQuery)->overdue()->count(),
            'my_tasks' => Task::where('assigned_to', $user->id)->where('status', '!=', 'done')->count(),
        ];

        $myTasks = Task::where('assigned_to', $user->id)
            ->with(['project:id,name'])
            ->latest()
            ->limit(10)
            ->get();

        $overdueTasks = (clone $taskQuery)
            ->overdue()
            ->with(['assignee:id,name', 'project:id,name'])
            ->limit(10)
            ->get();

        return response()->json([
            'stats' => $stats,
            'my_tasks' => $myTasks,
            'overdue_tasks' => $overdueTasks,
        ]);
    }
}
