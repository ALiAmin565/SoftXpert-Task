<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskDependency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TaskController extends Controller
{
    /**
     * Create a new TaskController instance.
     */
    public function __construct()
    {
        // Middleware is now handled in routes
    }

    /**
     * Display a listing of tasks with filtering options.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Validation: Only admin/manager can access other users' tasks
        if ($request->has('assigned_user') && $request->assigned_user != $user->id) {
            if ($user->role !== 'manager') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Only managers can view other users tasks.'
                ], 403);
            }
        }
        
        // Base query - users can only see their assigned tasks, managers can see all
        $query = $user->role === 'manager' 
            ? Task::with(['creator', 'assignee', 'dependencies', 'dependents'])
            : Task::with(['creator', 'assignee', 'dependencies', 'dependents'])
                  ->where('assigned_to', $user->id);

        // Apply filters
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }

        // Only managers can filter by assigned_user
        if ($request->has('assigned_user') && $user->role === 'manager') {
            $query->byAssignedUser($request->assigned_user);
        }

        // Apply date filters individually or together
        if ($request->has('due_date_from') && $request->has('due_date_to')) {
            $query->byDueDateRange($request->due_date_from, $request->due_date_to);
        } elseif ($request->has('due_date_from')) {
            $query->where('due_date', '>=', $request->due_date_from);
        } elseif ($request->has('due_date_to')) {
            $query->where('due_date', '<=', $request->due_date_to);
        }

        $tasks = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $tasks
        ]);
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Only managers can create tasks
        if ($user->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only managers can create tasks.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'nullable|exists:users,id',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'exists:tasks,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $task = Task::create([
                'title' => $request->title,
                'description' => $request->description,
                'due_date' => $request->due_date,
                'assigned_to' => $request->assigned_to,
                'created_by' => $user->id,
                'status' => 'pending'
            ]);

            // Add dependencies if provided
            if ($request->has('dependencies')) {
                foreach ($request->dependencies as $dependencyId) {
                    // Check for circular dependencies
                    if ($this->wouldCreateCircularDependency($task->id, $dependencyId)) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Cannot add dependency: would create circular dependency'
                        ], 422);
                    }

                    TaskDependency::create([
                        'task_id' => $task->id,
                        'depends_on_task_id' => $dependencyId
                    ]);
                }
            }

            DB::commit();

            $task->load(['creator', 'assignee', 'dependencies', 'dependents']);

            return response()->json([
                'success' => true,
                'message' => 'Task created successfully',
                'data' => $task
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error creating task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified task with dependencies.
     */
    public function show(Task $task): JsonResponse
    {
        $user = Auth::user();

        // Users can only view their assigned tasks, managers can view all
        if ($user->role !== 'manager' && $task->assigned_to !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only view tasks assigned to you.'
            ], 403);
        }

        $task->load(['creator', 'assignee', 'dependencies', 'dependents']);

        return response()->json([
            'success' => true,
            'data' => $task
        ]);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task): JsonResponse
    {
        $user = Auth::user();

        // Check permissions
        if ($user->role !== 'manager') {
            // Users can only update status of their assigned tasks
            if ($task->assigned_to !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You can only update tasks assigned to you.'
                ], 403);
            }

            // Users can only update status
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,in_progress,completed,canceled'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if task can be completed (all dependencies must be completed)
            if ($request->status === 'completed' && $task->hasPendingDependencies()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot complete task. Some dependencies are not yet completed.'
                ], 422);
            }

            $task->update(['status' => $request->status]);

        } else {
            // Managers can update all fields
            $validator = Validator::make($request->all(), [
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|in:pending,in_progress,completed,canceled',
                'due_date' => 'nullable|date|after_or_equal:today',
                'assigned_to' => 'nullable|exists:users,id',
                'dependencies' => 'nullable|array',
                'dependencies.*' => 'exists:tasks,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if task can be completed
            if ($request->has('status') && $request->status === 'completed' && $task->hasPendingDependencies()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot complete task. Some dependencies are not yet completed.'
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Update basic fields
                $task->update($request->only(['title', 'description', 'status', 'due_date', 'assigned_to']));

                // Update dependencies if provided
                if ($request->has('dependencies')) {
                    // Remove existing dependencies
                    TaskDependency::where('task_id', $task->id)->delete();

                    // Add new dependencies
                    foreach ($request->dependencies as $dependencyId) {
                        if ($this->wouldCreateCircularDependency($task->id, $dependencyId)) {
                            DB::rollBack();
                            return response()->json([
                                'success' => false,
                                'message' => 'Cannot add dependency: would create circular dependency'
                            ], 422);
                        }

                        TaskDependency::create([
                            'task_id' => $task->id,
                            'depends_on_task_id' => $dependencyId
                        ]);
                    }
                }

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Error updating task: ' . $e->getMessage()
                ], 500);
            }
        }

        $task->load(['creator', 'assignee', 'dependencies', 'dependents']);

        return response()->json([
            'success' => true,
            'message' => 'Task updated successfully',
            'data' => $task
        ]);
    }

    /**
     * Add dependencies to a task.
     */
    public function addDependencies(Request $request, Task $task): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only managers can manage task dependencies.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'dependencies' => 'required|array|min:1',
            'dependencies.*' => 'exists:tasks,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            foreach ($request->dependencies as $dependencyId) {
                // Check if dependency already exists
                $exists = TaskDependency::where('task_id', $task->id)
                    ->where('depends_on_task_id', $dependencyId)
                    ->exists();

                if ($exists) {
                    continue;
                }

                // Check for circular dependencies
                if ($this->wouldCreateCircularDependency($task->id, $dependencyId)) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Cannot add dependency {$dependencyId}: would create circular dependency"
                    ], 422);
                }

                TaskDependency::create([
                    'task_id' => $task->id,
                    'depends_on_task_id' => $dependencyId
                ]);
            }

            DB::commit();

            $task->load(['creator', 'assignee', 'dependencies', 'dependents']);

            return response()->json([
                'success' => true,
                'message' => 'Dependencies added successfully',
                'data' => $task
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error adding dependencies: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): JsonResponse
    {
        $user = Auth::user();

        if ($user->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only managers can delete tasks.'
            ], 403);
        }

        try {
            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Task deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting task: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if adding a dependency would create a circular dependency.
     */
    private function wouldCreateCircularDependency(int $taskId, int $dependencyId): bool
    {
        // If the dependency task depends on the current task (directly or indirectly),
        // adding this dependency would create a circular dependency
        return $this->hasPath($dependencyId, $taskId);
    }

    /**
     * Check if there's a dependency path from source to target.
     */
    private function hasPath(int $sourceId, int $targetId): bool
    {
        if ($sourceId === $targetId) {
            return true;
        }

        $visited = [];
        $queue = [$sourceId];

        while (!empty($queue)) {
            $currentId = array_shift($queue);
            
            if (in_array($currentId, $visited)) {
                continue;
            }
            
            $visited[] = $currentId;

            // Get all tasks that the current task depends on
            $dependencies = TaskDependency::where('task_id', $currentId)
                ->pluck('depends_on_task_id')
                ->toArray();

            foreach ($dependencies as $depId) {
                if ($depId === $targetId) {
                    return true;
                }
                
                if (!in_array($depId, $visited)) {
                    $queue[] = $depId;
                }
            }
        }

        return false;
    }

    /**
     * Get dependency chain for debugging circular dependencies.
     */
    public function getDependencyChain(Task $task): JsonResponse
    {
        $user = Auth::user();
        
        // Only managers can view dependency chains
        if ($user->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only managers can view dependency chains.'
            ], 403);
        }

        $dependencyChain = $this->buildDependencyChain($task->id);
        $cannotAddDependencies = $this->getTasksThatCannotBeAddedAsDependencies($task->id);
        
        return response()->json([
            'success' => true,
            'task_id' => $task->id,
            'task_title' => $task->title,
            'current_dependencies' => $dependencyChain,
            'cannot_add_as_dependencies' => $cannotAddDependencies,
            'message' => 'This shows current dependencies and tasks that cannot be added as dependencies (would create circular dependency)'
        ]);
    }

    /**
     * Build the complete dependency chain for a task.
     */
    private function buildDependencyChain(int $taskId, array $visited = []): array
    {
        if (in_array($taskId, $visited)) {
            return ['CIRCULAR_DEPENDENCY_DETECTED' => $taskId];
        }

        $visited[] = $taskId;
        $dependencies = [];

        $directDependencies = TaskDependency::where('task_id', $taskId)
            ->with('dependsOnTask')
            ->get();

        foreach ($directDependencies as $dependency) {
            $depTask = $dependency->dependsOnTask;
            $dependencies[] = [
                'id' => $depTask->id,
                'title' => $depTask->title,
                'status' => $depTask->status,
                'sub_dependencies' => $this->buildDependencyChain($depTask->id, $visited)
            ];
        }

        return $dependencies;
    }

    /**
     * Get all tasks that cannot be added as dependencies (would create circular dependency).
     */
    private function getTasksThatCannotBeAddedAsDependencies(int $taskId): array
    {
        $cannotAdd = [];
        
        // Get all tasks except the current task
        $allTasks = Task::where('id', '!=', $taskId)->get();
        
        foreach ($allTasks as $task) {
            // Check if adding this task as dependency would create circular dependency
            if ($this->wouldCreateCircularDependency($taskId, $task->id)) {
                $cannotAdd[] = [
                    'id' => $task->id,
                    'title' => $task->title,
                    'status' => $task->status,
                    'reason' => 'Would create circular dependency',
                    'dependency_path' => $this->getCircularDependencyPath($taskId, $task->id)
                ];
            }
        }
        
        // Also add the task itself (cannot depend on itself)
        $currentTask = Task::find($taskId);
        if ($currentTask) {
            array_unshift($cannotAdd, [
                'id' => $currentTask->id,
                'title' => $currentTask->title,
                'status' => $currentTask->status,
                'reason' => 'Cannot depend on itself',
                'dependency_path' => ['self-reference']
            ]);
        }
        
        return $cannotAdd;
    }

    /**
     * Get the path that would create circular dependency.
     */
    private function getCircularDependencyPath(int $taskId, int $dependencyId): array
    {
        $path = [];
        $visited = [];
        $queue = [['id' => $dependencyId, 'path' => [$dependencyId]]];

        while (!empty($queue)) {
            $current = array_shift($queue);
            $currentId = $current['id'];
            $currentPath = $current['path'];
            
            if ($currentId === $taskId) {
                return array_merge($currentPath, [$taskId]);
            }
            
            if (in_array($currentId, $visited)) {
                continue;
            }
            
            $visited[] = $currentId;
            
            $dependencies = TaskDependency::where('task_id', $currentId)
                ->pluck('depends_on_task_id')
                ->toArray();
                
            foreach ($dependencies as $depId) {
                if (!in_array($depId, $visited)) {
                    $queue[] = [
                        'id' => $depId,
                        'path' => array_merge($currentPath, [$depId])
                    ];
                }
            }
        }

        return [];
    }

    /**
     * Remove dependencies from a task.
     */
    public function removeDependencies(Request $request, Task $task): JsonResponse
    {
        $user = Auth::user();
        
        // Only managers can manage dependencies
        if ($user->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only managers can manage task dependencies.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'dependencies' => 'required|array',
            'dependencies.*' => 'integer|exists:tasks,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $dependenciesToRemove = $request->dependencies;

        // Remove the specified dependencies
        TaskDependency::where('task_id', $task->id)
            ->whereIn('depends_on_task_id', $dependenciesToRemove)
            ->delete();

        // Load the updated task with relationships
        $task->load(['creator', 'assignee', 'dependencies', 'dependents']);

        return response()->json([
            'success' => true,
            'message' => 'Dependencies removed successfully',
            'data' => $task
        ]);
    }

    /**
     * Remove all dependencies from a task.
     */
    public function removeAllDependencies(Task $task): JsonResponse
    {
        $user = Auth::user();
        
        // Only managers can manage dependencies
        if ($user->role !== 'manager') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Only managers can manage task dependencies.'
            ], 403);
        }

        // Remove all dependencies for this task
        TaskDependency::where('task_id', $task->id)->delete();

        // Load the updated task with relationships
        $task->load(['creator', 'assignee', 'dependencies', 'dependents']);

        return response()->json([
            'success' => true,
            'message' => 'All dependencies removed successfully',
            'data' => $task
        ]);
    }
}
