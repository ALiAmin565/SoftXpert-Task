# Task Management System API

A robust RESTful API for task management with JWT authentication, role-based access control, and task dependency management built with Laravel.

## 📋 Table of Contents

- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Configuration](#configuration)
- [Database Setup](#database-setup)
- [Running the Application](#running-the-application)
- [API Documentation](#api-documentation)
- [Authentication](#authentication)
- [Role-Based Access Control](#role-based-access-control)
- [Task Dependencies](#task-dependencies)
- [Testing](#testing)
- [Postman Collection](#postman-collection)
- [Database Schema](#database-schema)
- [Contributing](#contributing)
- [License](#license)

## ✨ Features

- **JWT Authentication**: Stateless authentication using JSON Web Tokens
- **Role-Based Access Control**: Manager and User roles with different permissions
- **Task Management**: Full CRUD operations for tasks
- **Task Dependencies**: Tasks can depend on other tasks with circular dependency prevention
- **Advanced Filtering**: Filter tasks by status, due date range, and assigned user
- **Data Validation**: Comprehensive input validation and error handling
- **Database Migrations & Seeders**: Easy database setup with sample data
- **API Documentation**: Complete Postman collection included

## 🔧 Requirements

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js & NPM (for frontend assets)

## 🚀 Installation

### 1. Clone the Repository

```bash
git clone https://github.com/ALiAmin565/SoftXpert-Task.git
cd Softxpert-Task
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Setup

```bash
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

## ⚙️ Configuration

### Database Configuration

Update your `.env` file with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=task_management
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

### JWT Configuration

The JWT secret is automatically generated when you run `php artisan jwt:secret`. You can also manually set it in your `.env` file:

```env
JWT_SECRET=your_jwt_secret_key
```

## 🗄️ Database Setup

### 1. Create Database

Create a MySQL database named `task_management` or use the name specified in your `.env` file.

### 2. Run Migrations

```bash
php artisan migrate
```

### 3. Seed Database (Optional)

```bash
php artisan db:seed
```

This will create sample users and tasks:

**Manager Users:**
- Email: `manager@softxpert.com`, Password: `password123`
- Email: `admin@softxpert.com`, Password: `password123`

**Regular Users:**
- Email: `alice@softxpert.com`, Password: `password123`
- Email: `bob@softxpert.com`, Password: `password123`
- Email: `charlie@softxpert.com`, Password: `password123`
- Email: `diana@softxpert.com`, Password: `password123`

## 🏃‍♂️ Running the Application

### Development Server

```bash
php artisan serve
```

The API will be available at `http://localhost:8000`


## 📚 API Documentation

### Base URL

```
http://localhost:8000/api
```

### Authentication Endpoints

| Method | Endpoint | Description | Access Control |
|--------|----------|-------------|--------|
| POST | `/auth/login` | User login | Public |
| POST | `/auth/register` | User registration | Public |
| POST | `/auth/logout` | User logout | Authenticated |
| POST | `/auth/refresh` | Refresh JWT token | Authenticated |
| GET | `/auth/me` | Get current user info | Authenticated |

### Task Management Endpoints

| Method | Endpoint | Description | Access Control |
|--------|----------|-------------|--------|
| GET | `/tasks` | Get all tasks (with filters) | Users: assigned tasks only, Managers: all tasks |
| POST | `/tasks` | Create new task | Managers only |
| GET | `/tasks/{id}` | Get task details | Users: assigned tasks only, Managers: all tasks |
| PUT | `/tasks/{id}` | Update task | Users: status only, Managers: all fields |
| DELETE | `/tasks/{id}` | Delete task | Managers only |
| POST | `/tasks/{id}/dependencies` | Add task dependencies | Managers only |
| DELETE | `/tasks/{id}/dependencies` | Remove specific dependencies | Managers only |
| DELETE | `/tasks/{id}/dependencies/all` | Remove all dependencies | Managers only |

### Query Parameters for GET /tasks

- `status`: Filter by task status (`pending`, `in_progress`, `completed`, `canceled`)
- `assigned_user`: Filter by assigned user ID (**managers only** - ignored for regular users)
- `due_date_from`: Filter tasks due after this date (YYYY-MM-DD)
- `due_date_to`: Filter tasks due before this date (YYYY-MM-DD)

### 🔐 Role-Based Task Filtering

#### **Manager Role:**
- Can see **all tasks** in the system
- Can filter by any parameter including `assigned_user`
- Has full access to all filtering options

#### **User Role:**
- Can **only see tasks assigned to them** (automatic filtering)
- Can use `status`, `due_date_from`, and `due_date_to` filters on their assigned tasks
- **Cannot use `assigned_user` parameter** - will receive 403 Unauthorized error if they try to access other users' tasks
- **Strict validation**: Any attempt to view other users' tasks is blocked with error message

### Step-by-Step API Usage Guide

#### Step 1: User Registration (Optional)
```bash
curl -X POST http://127.0.0.1:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "name": "New User",
    "email": "newuser@example.com",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "user"
  }'
```

#### Step 2: Login as Manager
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "manager@softxpert.com",
    "password": "password123"
  }'
```

**Response:**
```json
{
  "success": true,
  "user": {
    "id": 1,
    "name": "John Manager",
    "email": "manager@softxpert.com",
    "role": "manager"
  },
  "authorization": {
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "type": "bearer",
    "expires_in": 3600
  }
}
```

**⚠️ Important: Copy the token from the response for subsequent requests**

#### Step 3: Get Current User Info
```bash
curl -X GET http://127.0.0.1:8000/api/auth/me \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### Step 4: Get All Tasks (Manager View)
```bash
curl -X GET http://127.0.0.1:8000/api/tasks \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### Step 5: Get Tasks with Filters

**Manager Filters (can see all tasks):**
```bash
# Filter by status (shows all tasks with pending status)
curl -X GET "http://127.0.0.1:8000/api/tasks?status=pending" \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"

# Filter by assigned user (managers only - shows tasks assigned to user ID 3)
curl -X GET "http://127.0.0.1:8000/api/tasks?assigned_user=3" \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"

# Filter by due date from (shows tasks due after this date)
curl -X GET "http://127.0.0.1:8000/api/tasks?due_date_from=2025-01-01" \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"

# Filter by due date to (shows tasks due before this date)
curl -X GET "http://127.0.0.1:8000/api/tasks?due_date_to=2025-12-31" \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"

# Filter by due date range (shows tasks due between dates)
curl -X GET "http://127.0.0.1:8000/api/tasks?due_date_from=2025-01-01&due_date_to=2025-12-31" \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"

# Multiple filters (shows all tasks matching criteria)
curl -X GET "http://127.0.0.1:8000/api/tasks?status=pending&assigned_user=3&due_date_from=2025-01-01" \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"
```

**User Filters (only see their own tasks):**
```bash
# Filter by status (shows only user's own tasks with pending status)
curl -X GET "http://127.0.0.1:8000/api/tasks?status=pending" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"

# Filter by due date from (shows only user's own tasks due after this date)
curl -X GET "http://127.0.0.1:8000/api/tasks?due_date_from=2025-01-01" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"

# Filter by due date to (shows only user's own tasks due before this date)
curl -X GET "http://127.0.0.1:8000/api/tasks?due_date_to=2025-12-31" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"

# Filter by due date range (shows only user's own tasks in date range)
curl -X GET "http://127.0.0.1:8000/api/tasks?due_date_from=2025-01-01&due_date_to=2025-12-31" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"

# Multiple filters (shows only user's own tasks matching criteria)
curl -X GET "http://127.0.0.1:8000/api/tasks?status=pending&due_date_from=2025-01-01" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"
```

**⚠️ Important:** Users can apply filters, but they will always only see their own assigned tasks, not all tasks in the system.

#### Step 6: Get Specific Task Details
```bash
curl -X GET http://127.0.0.1:8000/api/tasks/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### Step 7: Create New Task (Manager Only)
```bash
curl -X POST http://127.0.0.1:8000/api/tasks \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "New Development Task",
    "description": "Implement new feature for the application",
    "due_date": "2025-12-31",
    "assigned_to": 3,
    "dependencies": [1, 2]
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Task created successfully",
  "data": {
    "id": 11,
    "title": "New Development Task",
    "description": "Implement new feature for the application",
    "status": "pending",
    "due_date": "2025-12-31T00:00:00.000000Z",
    "assigned_to": 3,
    "created_by": 1,
    "creator": {...},
    "assignee": {...},
    "dependencies": [...],
    "dependents": []
  }
}
```

#### Step 8: Add Dependencies to Existing Task (Manager Only)
```bash
curl -X POST http://127.0.0.1:8000/api/tasks/5/dependencies \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "dependencies": [1, 2, 3]
  }'
```

#### Step 8a: Remove Specific Dependencies from Task (Manager Only)
```bash
curl -X DELETE http://127.0.0.1:8000/api/tasks/5/dependencies \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "dependencies": [1, 2]
  }'
```

**Response:**
```json
{
  "success": true,
  "message": "Dependencies removed successfully",
  "data": {
    "id": 5,
    "title": "Task Title",
    "dependencies": [
      {
        "id": 3,
        "title": "Remaining Dependency",
        "status": "pending"
      }
    ]
  }
}
```

#### Step 8b: Remove All Dependencies from Task (Manager Only)
```bash
curl -X DELETE http://127.0.0.1:8000/api/tasks/5/dependencies/all \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

**Response:**
```json
{
  "success": true,
  "message": "All dependencies removed successfully",
  "data": {
    "id": 5,
    "title": "Task Title",
    "dependencies": []
  }
}
```

#### Step 9: Update Task (Manager - All Fields)
```bash
curl -X PUT http://127.0.0.1:8000/api/tasks/5 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "title": "Updated Task Title",
    "description": "Updated task description",
    "status": "in_progress",
    "due_date": "2025-12-31",
    "assigned_to": 4,
    "dependencies": [1, 2]
  }'
```

#### Step 10: Login as Regular User
```bash
curl -X POST http://127.0.0.1:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "alice@softxpert.com",
    "password": "password123"
  }'
```

#### Step 11: Get Tasks as User (Only Assigned Tasks)
**Note:** Users automatically see only tasks assigned to them. The `assigned_user` filter is ignored for regular users.

```bash
# As a user, this will only show tasks assigned to the logged-in user
curl -X GET http://127.0.0.1:8000/api/tasks \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"

# Users can still filter by status, but only see their own tasks
curl -X GET "http://127.0.0.1:8000/api/tasks?status=pending" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"

# Trying to access other users' tasks will return 403 error
curl -X GET "http://127.0.0.1:8000/api/tasks?assigned_user=3" \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"
```

**Error Response:**
```json
{
  "success": false,
  "message": "Unauthorized. Only managers can view other users tasks."
}
```

#### Step 12: Update Task Status (User - Status Only)
```bash
curl -X PUT http://127.0.0.1:8000/api/tasks/3 \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'
```

**⚠️ Note:** Users can only update the status of tasks assigned to them.

#### Step 13: Complete Task Dependencies First
Before completing a task, all its dependencies must be completed. Let's complete the dependencies first:

```bash
# Complete dependency task (Task 1)
curl -X PUT http://127.0.0.1:8000/api/tasks/1 \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'
```

#### Step 14: Try to Complete Task with Pending Dependencies (Will Fail)
```bash
curl -X PUT http://127.0.0.1:8000/api/tasks/4 \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'
```

**Response (Error if dependencies not completed):**
```json
{
  "success": false,
  "message": "Cannot complete task. Some dependencies are not yet completed."
}
```

#### Step 15: Successfully Complete Task (After Dependencies)

**Prerequisites:**
- You must be logged in as a user assigned to the task
- All dependency tasks must be completed first

**Process:**

1. **Check task dependencies first:**
```bash
curl -X GET http://127.0.0.1:8000/api/tasks/4 \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Accept: application/json"
```

2. **Complete all dependency tasks:**
```bash
# Complete dependency task 1
curl -X PUT http://127.0.0.1:8000/api/tasks/1 \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'

# Complete dependency task 2 (if exists)
curl -X PUT http://127.0.0.1:8000/api/tasks/2 \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'
```

3. **Complete the main task:**
```bash
curl -X PUT http://127.0.0.1:8000/api/tasks/4 \
  -H "Authorization: Bearer USER_JWT_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'
```

**Real Example with Seeded Data (Complete Task 8 - Deploy to Production):**

```bash
# 1. Check Task 8 dependencies
curl -X GET http://127.0.0.1:8000/api/tasks/8 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Accept: application/json"

# 2. Complete Task 7 (Write Unit Tests) first
curl -X PUT http://127.0.0.1:8000/api/tasks/7 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'

# 3. Now complete Task 8
curl -X PUT http://127.0.0.1:8000/api/tasks/8 \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "status": "completed"
  }'
```

**Success Response:**
```json
{
  "success": true,
  "message": "Task updated successfully",
  "data": {
    "id": 4,
    "title": "Task Title",
    "description": "Task description",
    "status": "completed",
    "due_date": "2025-12-31T00:00:00.000000Z",
    "assigned_to": 3,
    "created_by": 1,
    "creator": {
      "id": 1,
      "name": "John Manager",
      "email": "manager@softxpert.com",
      "role": "manager"
    },
    "assignee": {
      "id": 3,
      "name": "Alice Developer",
      "email": "alice@softxpert.com",
      "role": "user"
    },
    "dependencies": [
      {
        "id": 1,
        "status": "completed"
      },
      {
        "id": 2,
        "status": "completed"
      }
    ],
    "dependents": []
  }
}
```

**⚠️ Important Notes:**
- Replace `USER_JWT_TOKEN` with your actual JWT token from login
- Users can only complete tasks assigned to them
- Managers can complete any task
- System validates all dependencies are completed before allowing task completion

#### Step 16: Delete Task (Manager Only)
```bash
curl -X DELETE http://127.0.0.1:8000/api/tasks/10 \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### Step 17: Refresh JWT Token
```bash
curl -X POST http://127.0.0.1:8000/api/auth/refresh \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

#### Step 18: Logout
```bash
curl -X POST http://127.0.0.1:8000/api/auth/logout \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -H "Accept: application/json"
```

### Common Error Responses

#### Unauthorized Access
```json
{
  "success": false,
  "message": "Unauthorized. Only managers can create tasks."
}
```

#### Validation Error
```json
{
  "success": false,
  "message": "Validation error",
  "errors": {
    "title": ["The title field is required."],
    "assigned_to": ["The selected assigned to is invalid."]
  }
}
```

#### Dependency Validation Error
```json
{
  "success": false,
  "message": "Cannot add dependency: would create circular dependency"
}
```

## 🔐 Authentication

The API uses JWT (JSON Web Tokens) for authentication. Include the token in the Authorization header:

```
Authorization: Bearer YOUR_JWT_TOKEN
```

Tokens expire after 60 minutes by default and can be refreshed using the `/auth/refresh` endpoint.

## 👥 Role-Based Access Control

### Manager Role
- Create, update, and delete tasks
- Assign tasks to users
- View all tasks
- Manage task dependencies
- Update all task fields

### User Role
- View only assigned tasks
- Update only the status of assigned tasks
- Cannot create, delete, or assign tasks

## 🔗 Task Dependencies

Tasks can depend on other tasks with the following rules:

1. **Dependency Validation**: A task cannot be completed until all its dependencies are completed
2. **Circular Dependency Prevention**: The system prevents circular dependencies using graph traversal
3. **Cascade Operations**: When a task is deleted, its dependencies are also removed

### Example Dependency Chain
```
Task A (completed) ← Task B (in_progress) ← Task C (pending)
```
Task C cannot be completed until Task B is completed, and Task B cannot be completed until Task A is completed.


## 🧪 Testing

Run the test suite:

```bash
php artisan test
```

For feature testing with database:

```bash
php artisan test --env=testing
```

## 📮 Postman Collection

Import the `Task_Management_API.postman_collection.json` file into Postman for easy API testing.

The collection includes:
- Pre-configured requests for all endpoints
- Environment variables for base URL and JWT token
- Automatic token extraction from login responses
- Example requests for both manager and user roles

### Collection Variables
- `base_url`: API base URL (default: http://localhost:8000/api)
- `jwt_token`: JWT token (automatically set after login)

## 🗃️ Database Schema

### Users Table
- `id`: Primary key
- `name`: User's full name
- `email`: Unique email address
- `password`: Hashed password
- `role`: User role (manager/user)
- `email_verified_at`: Email verification timestamp
- `remember_token`: Remember token for sessions
- `created_at`, `updated_at`: Timestamps

### Tasks Table
- `id`: Primary key
- `title`: Task title
- `description`: Task description (nullable)
- `status`: Task status (pending/in_progress/completed/canceled)
- `due_date`: Task due date (nullable)
- `assigned_to`: Foreign key to users table (nullable)
- `created_by`: Foreign key to users table
- `created_at`, `updated_at`: Timestamps

### Task Dependencies Table
- `id`: Primary key
- `task_id`: Foreign key to tasks table
- `depends_on_task_id`: Foreign key to tasks table
- `created_at`, `updated_at`: Timestamps
- Unique constraint on (task_id, depends_on_task_id)

For a complete ERD, see [ERD.md](ERD.md).

## 🔒 Security Features

- **JWT Authentication**: Stateless and secure
- **Password Hashing**: Using Laravel's built-in bcrypt
- **Input Validation**: Comprehensive validation rules
- **SQL Injection Prevention**: Using Eloquent ORM
- **CORS Support**: Configurable cross-origin requests
- **Rate Limiting**: API rate limiting (can be configured)

## 📝 API Response Format

All API responses follow a consistent format:

### Success Response
```json
{
    "success": true,
    "data": {},
    "message": "Operation successful"
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error description",
    "errors": {}
}
```

## 🔍 Debugging Circular Dependencies

### Get Dependency Chain and Blocked Tasks

Use this endpoint to debug circular dependency issues and see which tasks cannot be added as dependencies:

```bash
# Get current dependencies and tasks that cannot be added as dependencies
curl -X GET http://127.0.0.1:8000/api/tasks/4/dependency-chain \
  -H "Authorization: Bearer MANAGER_JWT_TOKEN" \
  -H "Accept: application/json"
```

**Response Example:**
```json
{
  "success": true,
  "task_id": 4,
  "task_title": "Deploy to Production",
  "current_dependencies": [
    {
      "id": 7,
      "title": "Write Unit Tests",
      "status": "pending",
      "sub_dependencies": [
        {
          "id": 6,
          "title": "Implement Frontend Components",
          "status": "pending",
          "sub_dependencies": []
        }
      ]
    }
  ],
  "cannot_add_as_dependencies": [
    {
      "id": 4,
      "title": "Deploy to Production",
      "status": "pending",
      "reason": "Cannot depend on itself",
      "dependency_path": ["self-reference"]
    },
    {
      "id": 1,
      "title": "Setup Database",
      "status": "completed",
      "reason": "Would create circular dependency",
      "dependency_path": [1, 6, 7, 4]
    }
  ],
  "message": "This shows current dependencies and tasks that cannot be added as dependencies (would create circular dependency)"
}
```

**Understanding the Response:**

- **`current_dependencies`**: Shows the complete dependency tree for the task
- **`cannot_add_as_dependencies`**: Lists all tasks that cannot be added as dependencies
  - **`reason`**: Why the task cannot be added
  - **`dependency_path`**: The path that would create the circular dependency

**How to Fix Circular Dependencies:**

1. **Identify the circular path** from the `dependency_path` field
2. **Remove conflicting dependencies** using the remove dependencies endpoint
3. **Restructure your dependency chain** to avoid the circular reference

## 🚀 Deployment

### Production Checklist

1. Set `APP_ENV=production` in `.env`
2. Set `APP_DEBUG=false` in `.env`
3. Configure proper database credentials
4. Set up SSL certificates
5. Configure web server (Nginx/Apache)
6. Set up process manager (Supervisor)
7. Configure caching and queues
8. Set up monitoring and logging

### Environment Variables

Key environment variables for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_PORT=3306
DB_DATABASE=your_db_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

JWT_SECRET=your_production_jwt_secret
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## 📞 Support

For support and questions:
- Create an issue in the repository
- Contact: [your-email@example.com]

## 🙏 Acknowledgments

- Laravel Framework
- JWT-Auth Package
- Docker Community
- Postman Team
