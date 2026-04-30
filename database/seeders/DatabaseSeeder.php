<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Demo admin
        $admin = User::updateOrCreate(
            ['email' => 'admin@taskmanager.com'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('Admin@123'),
                'role' => 'admin',
            ]
        );

        // Demo members
        $alice = User::updateOrCreate(
            ['email' => 'alice@taskmanager.com'],
            ['name' => 'Alice', 'password' => Hash::make('Member@123'), 'role' => 'member']
        );

        $bob = User::updateOrCreate(
            ['email' => 'bob@taskmanager.com'],
            ['name' => 'Bob', 'password' => Hash::make('Member@123'), 'role' => 'member']
        );

        // Demo project
        $project = Project::firstOrCreate(
            ['name' => 'Website Redesign'],
            [
                'description' => 'Refresh the marketing site with new branding and CMS.',
                'owner_id' => $admin->id,
                'status' => 'active',
            ]
        );
        $project->members()->syncWithoutDetaching([$alice->id, $bob->id]);

        // Demo tasks
        $tasks = [
            ['title' => 'Wireframe homepage', 'status' => 'done', 'priority' => 'high', 'assigned_to' => $alice->id, 'due_date' => now()->subDays(5)],
            ['title' => 'Design system tokens', 'status' => 'in_progress', 'priority' => 'high', 'assigned_to' => $alice->id, 'due_date' => now()->addDays(3)],
            ['title' => 'CMS migration plan', 'status' => 'todo', 'priority' => 'medium', 'assigned_to' => $bob->id, 'due_date' => now()->subDays(2)],
            ['title' => 'SEO audit', 'status' => 'todo', 'priority' => 'low', 'assigned_to' => $bob->id, 'due_date' => now()->addDays(10)],
        ];

        foreach ($tasks as $t) {
            Task::firstOrCreate(
                ['project_id' => $project->id, 'title' => $t['title']],
                array_merge($t, ['created_by' => $admin->id])
            );
        }
    }
}
