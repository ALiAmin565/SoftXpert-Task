# Task Management System - Entity Relationship Diagram (ERD)

## Database Schema Overview

The Task Management System consists of the following main entities:

### 1. Users Table
- **id** (Primary Key, Auto Increment)
- **name** (String, Required)
- **email** (String, Unique, Required)
- **password** (String, Required, Hashed)
- **role** (Enum: 'manager', 'user', Default: 'user')
- **email_verified_at** (Timestamp, Nullable)
- **remember_token** (String, Nullable)
- **created_at** (Timestamp)
- **updated_at** (Timestamp)

### 2. Tasks Table
- **id** (Primary Key, Auto Increment)
- **title** (String, Required)
- **description** (Text, Nullable)
- **status** (Enum: 'pending', 'in_progress', 'completed', 'canceled', Default: 'pending')
- **due_date** (Date, Nullable)
- **assigned_to** (Foreign Key -> users.id, Nullable)
- **created_by** (Foreign Key -> users.id, Required)
- **created_at** (Timestamp)
- **updated_at** (Timestamp)

### 3. Task Dependencies Table (Many-to-Many Relationship)
- **id** (Primary Key, Auto Increment)
- **task_id** (Foreign Key -> tasks.id, Required)
- **depends_on_task_id** (Foreign Key -> tasks.id, Required)
- **created_at** (Timestamp)
- **updated_at** (Timestamp)

## Relationships

### Users ↔ Tasks
- **One-to-Many (Creator)**: One user can create many tasks
  - `users.id` → `tasks.created_by`
- **One-to-Many (Assignee)**: One user can be assigned to many tasks
  - `users.id` → `tasks.assigned_to`

### Tasks ↔ Task Dependencies
- **Many-to-Many (Self-Referencing)**: Tasks can depend on other tasks
  - `tasks.id` → `task_dependencies.task_id`
  - `tasks.id` → `task_dependencies.depends_on_task_id`

## Business Rules

1. **Task Dependencies**: A task cannot be marked as completed until all its dependencies are completed
2. **Role-Based Access**:
   - **Managers**: Can create, update, and assign tasks to any user
   - **Users**: Can only view tasks assigned to them and update the status of their assigned tasks
3. **Status Transitions**: Tasks can transition between statuses with proper validation
4. **Circular Dependencies**: System should prevent circular dependencies between tasks

## Indexes

- `users.email` (Unique Index)
- `tasks.assigned_to` (Index for filtering)
- `tasks.status` (Index for filtering)
- `tasks.due_date` (Index for filtering)
- `tasks.created_by` (Index for filtering)
- `task_dependencies.task_id` (Index)
- `task_dependencies.depends_on_task_id` (Index)
- Composite index on `task_dependencies(task_id, depends_on_task_id)` for uniqueness

## Visual ERD

```
┌─────────────────┐       ┌─────────────────┐       ┌─────────────────┐
│     Users       │       │     Tasks       │       │ Task Dependencies│
├─────────────────┤       ├─────────────────┤       ├─────────────────┤
│ id (PK)         │◄──────┤ created_by (FK) │       │ id (PK)         │
│ name            │       │ assigned_to (FK)│──────►│ task_id (FK)    │
│ email (UNIQUE)  │       │ id (PK)         │◄──────┤ depends_on_task │
│ password        │       │ title           │       │   _id (FK)      │
│ role (ENUM)     │       │ description     │       │ created_at      │
│ email_verified  │       │ status (ENUM)   │       │ updated_at      │
│ remember_token  │       │ due_date        │       └─────────────────┘
│ created_at      │       │ created_at      │
│ updated_at      │       │ updated_at      │
└─────────────────┘       └─────────────────┘

Relationships:
- Users (1) ──── (N) Tasks (created_by)
- Users (1) ──── (N) Tasks (assigned_to)  
- Tasks (N) ──── (N) Tasks (via task_dependencies)
```

## Sample Data Structure

### Users
```json
{
  "id": 1,
  "name": "John Manager",
  "email": "manager@example.com",
  "role": "manager"
}
```

### Tasks
```json
{
  "id": 1,
  "title": "Setup Database",
  "description": "Create and configure the database schema",
  "status": "pending",
  "due_date": "2024-01-15",
  "assigned_to": 2,
  "created_by": 1
}
```

### Task Dependencies
```json
{
  "id": 1,
  "task_id": 2,
  "depends_on_task_id": 1
}
```
