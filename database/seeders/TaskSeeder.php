<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\TaskDependency;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $manager = User::where('role', 'manager')->first();
        $users = User::where('role', 'user')->get();

        // Create sample tasks
        $task1 = Task::create([
            'title' => 'Setup Development Environment',
            'description' => 'Install and configure all necessary development tools and dependencies',
            'status' => 'completed',
            'due_date' => now()->subDays(10),
            'assigned_to' => $users->get(0)->id,
            'created_by' => $manager->id,
        ]);

        $task2 = Task::create([
            'title' => 'Design Database Schema',
            'description' => 'Create ERD and design the database structure for the application',
            'status' => 'completed',
            'due_date' => now()->subDays(8),
            'assigned_to' => $users->get(1)->id,
            'created_by' => $manager->id,
        ]);

        $task3 = Task::create([
            'title' => 'Implement User Authentication',
            'description' => 'Develop JWT-based authentication system with role-based access control',
            'status' => 'in_progress',
            'due_date' => now()->addDays(3),
            'assigned_to' => $users->get(0)->id,
            'created_by' => $manager->id,
        ]);

        $task4 = Task::create([
            'title' => 'Create Task Management API',
            'description' => 'Develop RESTful API endpoints for task CRUD operations',
            'status' => 'pending',
            'due_date' => now()->addDays(7),
            'assigned_to' => $users->get(0)->id,
            'created_by' => $manager->id,
        ]);

        $task5 = Task::create([
            'title' => 'Design UI/UX Mockups',
            'description' => 'Create wireframes and mockups for the task management interface',
            'status' => 'in_progress',
            'due_date' => now()->addDays(5),
            'assigned_to' => $users->get(1)->id,
            'created_by' => $manager->id,
        ]);

        $task6 = Task::create([
            'title' => 'Implement Frontend Components',
            'description' => 'Develop React components for task management interface',
            'status' => 'pending',
            'due_date' => now()->addDays(10),
            'assigned_to' => $users->get(1)->id,
            'created_by' => $manager->id,
        ]);

        $task7 = Task::create([
            'title' => 'Write Unit Tests',
            'description' => 'Create comprehensive unit tests for all API endpoints',
            'status' => 'pending',
            'due_date' => now()->addDays(12),
            'assigned_to' => $users->get(2)->id,
            'created_by' => $manager->id,
        ]);

        $task8 = Task::create([
            'title' => 'Setup CI/CD Pipeline',
            'description' => 'Configure automated testing and deployment pipeline',
            'status' => 'pending',
            'due_date' => now()->addDays(15),
            'assigned_to' => $users->get(3)->id,
            'created_by' => $manager->id,
        ]);

        $task9 = Task::create([
            'title' => 'Performance Testing',
            'description' => 'Conduct load testing and performance optimization',
            'status' => 'pending',
            'due_date' => now()->addDays(18),
            'assigned_to' => $users->get(2)->id,
            'created_by' => $manager->id,
        ]);

        $task10 = Task::create([
            'title' => 'Documentation',
            'description' => 'Write comprehensive API documentation and user guides',
            'status' => 'pending',
            'due_date' => now()->addDays(20),
            'assigned_to' => $users->get(0)->id,
            'created_by' => $manager->id,
        ]);

        // Create task dependencies
        // Task 3 (Auth) depends on Task 1 (Setup) and Task 2 (Database)
        TaskDependency::create([
            'task_id' => $task3->id,
            'depends_on_task_id' => $task1->id,
        ]);

        TaskDependency::create([
            'task_id' => $task3->id,
            'depends_on_task_id' => $task2->id,
        ]);

        // Task 4 (API) depends on Task 3 (Auth)
        TaskDependency::create([
            'task_id' => $task4->id,
            'depends_on_task_id' => $task3->id,
        ]);

        // Task 6 (Frontend) depends on Task 4 (API) and Task 5 (Design)
        TaskDependency::create([
            'task_id' => $task6->id,
            'depends_on_task_id' => $task4->id,
        ]);

        TaskDependency::create([
            'task_id' => $task6->id,
            'depends_on_task_id' => $task5->id,
        ]);

        // Task 7 (Tests) depends on Task 4 (API)
        TaskDependency::create([
            'task_id' => $task7->id,
            'depends_on_task_id' => $task4->id,
        ]);

        // Task 8 (CI/CD) depends on Task 7 (Tests)
        TaskDependency::create([
            'task_id' => $task8->id,
            'depends_on_task_id' => $task7->id,
        ]);

        // Task 9 (Performance) depends on Task 6 (Frontend) and Task 7 (Tests)
        TaskDependency::create([
            'task_id' => $task9->id,
            'depends_on_task_id' => $task6->id,
        ]);

        TaskDependency::create([
            'task_id' => $task9->id,
            'depends_on_task_id' => $task7->id,
        ]);

        // Task 10 (Documentation) depends on Task 9 (Performance)
        TaskDependency::create([
            'task_id' => $task10->id,
            'depends_on_task_id' => $task9->id,
        ]);
    }
}
