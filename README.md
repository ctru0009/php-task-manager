# PHP Task Manager

A simple task management application built with plain PHP, MySQL, and HTML/CSS.

## Features

- User registration and login with secure password hashing
- Create, read, update, and delete tasks
- Task status management (pending, in progress, completed)
- Priority levels (low, medium, high)
- Filter tasks by status
- Server-side validation
- Prepared statements (PDO) for secure database operations

## Technology Stack

- PHP (no framework)
- MySQL database
- PDO for database operations
- HTML5 + CSS3
- Responsive design

## Project Structure

```
php-task-manager/
├── config/
│   └── database.php          # Database connection configuration
├── controllers/
│   ├── AuthController.php   # User authentication logic
│   └── TaskController.php    # Task management logic
├── models/
│   ├── User.php              # User model
│   └── Task.php              # Task model
├── views/
│   ├── auth/
│   │   ├── login.php         # Login page
│   │   └── register.php      # Registration page
│   ├── task/
│   │   ├── index.php         # Task list
│   │   ├── create.php        # Create task form
│   │   ├── edit.php          # Edit task form
│   │   └── delete.php        # Delete confirmation
│   └── layout.php            # Main layout template
├── public/
│   └── css/
│       └── style.css         # Application styles
├── index.php                 # Main entry point
└── schema.sql                # Database schema
```

## Installation

1. Import the database schema:
   ```bash
   mysql -u root -p < schema.sql
   ```

2. Configure database connection in `config/database.php`:
   ```php
   $dsn = 'mysql:host=localhost;dbname=task_manager;charset=utf8mb4';
   $this->connection = new PDO($dsn, 'your_username', 'your_password');
   ```

3. Configure your web server to point to the project directory

4. Access the application at `http://localhost/`

## Usage

1. Register a new account
2. Login with your credentials
3. Create tasks with title, description, and priority
4. Manage tasks (edit, delete, update status)
5. Filter tasks by status

## Development

Used Claude CLI and MCP to generate initial PHP boilerplate, validate SQL queries, refactor controllers, and explain PHP syntax.

## Security

- Password hashing using `password_hash()` and `password_verify()`
- Prepared statements (PDO) to prevent SQL injection
- Session management for user authentication
- Input validation and sanitization
- CSRF protection (recommended for production)

## License

This project is for demonstration purposes.
