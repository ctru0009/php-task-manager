# PHP Concepts - Complete Guide

This document explains all PHP concepts used in the Task Manager project, from basics to advanced topics.

Every example names the file it comes from instead of a line number, so the references do not drift as the code changes. Open the file to see the current version.

---

## Table of Contents

1. [Basic PHP Syntax](#1-basic-php-syntax)
2. [Variables & Data Types](#2-variables--data-types)
3. [Operators](#3-operators)
4. [Control Structures](#4-control-structures)
5. [Functions](#5-functions)
6. [Object-Oriented Programming](#6-object-oriented-programming)
7. [File Organization & Imports](#7-file-organization--imports)
8. [Superglobals](#8-superglobals)
9. [Error & Exception Handling](#9-error--exception-handling)
10. [HTTP Features](#10-http-features)
11. [Session Management](#11-session-management)
12. [Database Operations (PDO)](#12-database-operations-pdo)
13. [Security Functions](#13-security-functions)
14. [String & Array Manipulation](#14-string--array-manipulation)

---

## 1. Basic PHP Syntax

### PHP Opening Tags

PHP code must start with `<?php` tag. This tells the server to interpret the following code as PHP.

**Example from `index.php`:**
```php
<?php

require_once __DIR__ . '/config/headers.php';
require_once __DIR__ . '/config/session.php';

session_start();

require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/config/database.php';
```

### Statements and Semicolons

Every PHP statement must end with a semicolon `;`. This separates instructions.

**Example:**
```php
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';
```

### Comments

PHP supports single-line comments (`//`) and multi-line comments (`/* */`).

**Example:**
```php
// This is a single-line comment
/* This is a
   multi-line comment */
```

---

## 2. Variables & Data Types

### Variables

Variables in PHP start with a dollar sign `$` followed by the variable name. They are case-sensitive.

**Example from `models/User.php`:**
```php
private $db;
```

**Example from `index.php`:**
```php
$controller = $_GET['controller'] ?? 'auth';
```

### Data Types

**String:** Text data enclosed in single or double quotes
```php
$username = 'john';
$email = "john@example.com";
```

**Integer:** Whole numbers
```php
$userId = 123;
```

**Boolean:** `true` or `false`
```php
$isActive = true;
```

**Null:** Represents no value
```php
$status = null;
```

**Array:** Ordered collection of values
```php
$errors = [];
$errors[] = 'Username is required';
```

### String Concatenation

Use the `.` operator to join strings.

**Example:**
```php
$message = "Hello " . $username;
```

---

## 3. Operators

### Comparison Operators

- `==` - Equal (values match, type doesn't matter)
- `===` - Identical (values and types match)
- `!=` - Not equal
- `!==` - Not identical
- `>` - Greater than
- `<` - Less than

**Example from `controllers/AuthController.php`:**
```php
!filter_var($email, FILTER_VALIDATE_EMAIL)
```

**Example from `controllers/AuthController.php`:**
```php
if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match';
}
```

### Logical Operators

- `&&` or `and` - Both conditions must be true
- `||` or `or` - At least one condition must be true
- `!` - Not (reverses the condition)

**Example from `controllers/AuthController.php`:**
```php
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}
```

### Null Coalescing Operator (`??`)

Returns the first value if it exists and is not null, otherwise returns the second value.

**Example from `index.php`:**
```php
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';
```

If `$_GET['controller']` exists, use its value; otherwise use 'auth' as default.

### String Comparison

Used in conditional checks.

**Example from `views/task/index.php`:**
```php
isset($_GET['status']) && $_GET['status'] === 'pending'
```

---

## 4. Control Structures

### If/Elseif/Else Statements

Execute different code based on conditions.

**Example from `controllers/AuthController.php`:**
```php
if (empty($username)) {
    $errors[] = 'Username is required';
} elseif (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters';
}
```

### Switch Statements

Test a single variable against multiple values.

**Example from `index.php`:**
```php
switch ($controller) {
    case 'auth':
        require_once __DIR__ . '/controllers/AuthController.php';
        $authController = new AuthController();
        
        switch ($action) {
            case 'register':
                $authController->register();
                break;
            case 'login':
                $authController->login();
                break;
            case 'logout':
                $authController->logout();
                break;
            default:
                $authController->login();
        }
        break;
}
```

### Foreach Loops

Iterate over arrays.

**Example from `views/task/index.php`:**
```php
<?php $index = 0; foreach ($tasks as $task): $index++; ?>
    <div class="task task-<?php echo $task['status']; ?>">
        <h2><?php echo htmlspecialchars($task['title']); ?></h2>
    </div>
<?php endforeach; ?>
```

### Ternary Operator

Shorthand for if-else statements.

**Example from `models/Task.php`:**
```php
if ($status) {
    $stmt = $this->db->prepare('...');
} else {
    $stmt = $this->db->prepare('...');
}
```

---

## 5. Functions

### Built-in Functions

**`trim()`** - Removes whitespace from both ends of a string
```php
$username = trim($_POST['username'] ?? '');
```
**Reference:** `controllers/AuthController.php`

**`strlen()`** - Returns string length
```php
elseif (strlen($username) < 3) {
    $errors[] = 'Username must be at least 3 characters';
}
```
**Reference:** `controllers/AuthController.php`

**`empty()`** - Checks if a variable is empty
```php
if (empty($username)) {
    $errors[] = 'Username is required';
}
```
**Reference:** `controllers/AuthController.php`

**`isset()`** - Checks if a variable is set and is not null
```php
isset($_GET['status'])
```
**Reference:** `views/task/index.php`

**`in_array()`** - Checks if a value exists in an array
```php
if (in_array($status, ['pending', 'in_progress', 'completed'])) {
    // valid status
}
```

**`date()`** - Formats a date/time
```php
date('M d, Y', strtotime($task['created_at']))
```
**Reference:** `views/task/index.php`

**`filter_var()`** - Filters a variable with a specified filter
```php
elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Invalid email format';
}
```
**Reference:** `controllers/AuthController.php`

### User-Defined Functions

Methods are functions defined inside classes.

**Example from `models/User.php`:**
```php
public function register($username, $email, $password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $stmt = $this->db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
        $stmt->execute([$username, $email, $hashedPassword]);
        return $this->db->lastInsertId();
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            throw new ValidationException('Username or email already exists');
        }
        throw $e;
    }
}
```

---

## 6. Object-Oriented Programming

### Classes

Classes are blueprints for creating objects. They define properties and methods.

**Example from `config/database.php`:**
```php
class Database {
    private static $instance = null;
    private $connection;
    // ...
}
```

### Objects

Instances of classes created using the `new` keyword.

**Example from `index.php`:**
```php
$authController = new AuthController();
```

### Properties (Member Variables)

Variables that belong to a class.

**Example from `models/User.php`:**
```php
private $db;
```

### Visibility Modifiers

- `private` - Only accessible within the class
- `public` - Accessible from anywhere
- `protected` - Accessible within class and its children

**Example:**
```php
private $db;              // Only accessible inside User class
public function login() {  // Accessible from anywhere
    // ...
}
```

### Methods (Member Functions)

Functions that belong to a class.

**Example from `models/User.php`:**
```php
public function login($username, $password) {
    $stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        unset($user['password']);
        return $user;
    }
    
    return null;
}
```

### Constructor (`__construct()`)

Special method called automatically when an object is created.

**Example from `models/User.php`:**
```php
public function __construct() {
    $this->db = Database::getConnection();
}
```

### $this Keyword

Refers to the current object instance.

**Example from `models/User.php`:**
```php
$this->db = Database::getConnection();
```

### Static Properties and Methods

Belong to the class itself, not to any specific object instance. Accessed with `::` operator.

**Example from `config/database.php`:**
```php
private static $instance = null;
```

**Example from `config/database.php`:**
```php
public static function getInstance() {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance->connection;
}
```

### Singleton Pattern

Design pattern that ensures only one instance of a class exists.

**Example from `config/database.php`:**
```php
public static function getInstance() {
    if (self::$instance === null) {
        self::$instance = new self();
    }
    return self::$instance->connection;
}
```

### self Keyword

Refers to the current class (used with static members).

**Example:**
```php
self::$instance = new self();
```

---

## 7. File Organization & Imports

### require_once

Includes and evaluates a file only once, preventing duplicate inclusions and errors.

**Example from `index.php`:**
```php
require_once __DIR__ . '/config/database.php';
```

**Example from `models/User.php`:**
```php
require_once __DIR__ . '/../config/database.php';
```

### require

Same as `require_once`, but doesn't check if file was already included.

**Example from `controllers/AuthController.php`:**
```php
require __DIR__ . '/../views/auth/register.php';
```

### `__DIR__` Magic Constant

Returns the directory path of the current file.

**Example from `index.php`:**
```php
require_once __DIR__ . '/config/database.php';
```

If `index.php` is in `/var/www/html/`, then `__DIR__` is `/var/www/html`.

### File Paths

- `./` - Current directory
- `../` - Parent directory
- Absolute paths start with `/` (Unix) or drive letter (Windows)

**Example:**
```php
__DIR__ . '/../config/database.php'
```

---

## 8. Superglobals

Superglobals are built-in variables that are always accessible.

### `$_SERVER`

Contains information about server and execution environment.

**Example from `controllers/AuthController.php`:**
```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require __DIR__ . '/../views/auth/register.php';
    return;
}
```

Common `$_SERVER` values:
- `$_SERVER['REQUEST_METHOD']` - HTTP method (GET, POST, etc.)
- `$_SERVER['REQUEST_URI']` - The requested URI
- `$_SERVER['HTTP_HOST']` - Host header

### `$_GET`

Contains data sent via URL query string.

**Example from `index.php`:**
```php
$controller = $_GET['controller'] ?? 'auth';
$action = $_GET['action'] ?? 'login';
```

URL: `http://example.com/index.php?controller=task&action=index`

### `$_POST`

Contains data sent via HTTP POST (usually from forms).

**Example from `controllers/AuthController.php`:**
```php
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
```

### `$_SESSION`

Contains session variables that persist across page requests.

**Example from `controllers/AuthController.php`:**
```php
$_SESSION['user_id'] = $userId;
$_SESSION['username'] = $username;
```

**Example from `controllers/AuthController.php`:**
```php
session_regenerate_id(true);
csrf_rotate();
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
```

---

## 9. Error & Exception Handling

### Try-Catch Blocks

Handle exceptions that might occur during code execution.

**Example from `config/database.php`:**
```php
$host = self::env('DB_HOST');
$name = self::env('DB_NAME');
$user = self::env('DB_USER');
$password = self::env('DB_PASSWORD');

try {
    $dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
    $this->connection = new PDO($dsn, $user, $password);
    $this->connection->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION,
    );
    $this->connection->setAttribute(
        PDO::ATTR_DEFAULT_FETCH_MODE,
        PDO::FETCH_ASSOC,
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Service unavailable: database connection failed.');
}
```

The connection details come from the environment and a missing variable stops the request before PDO is even created (see `config/database.php`), so a misconfigured deployment fails closed instead of guessing credentials.

### Throwing Exceptions

Create and throw an exception to signal an error.

**Example from `models/User.php`:**
```php
if ($e->getCode() == 23000) {
    throw new ValidationException('Username or email already exists');
}
throw $e;
```

`ValidationException` (in `includes/exceptions.php`) means "the user did something the rules do not allow", so the controller can show that message. Anything else is an unexpected failure: the controller logs it and shows a generic message instead of leaking the database error.

### PDOException

Special exception type for database errors.

**Example from `models/User.php`:**
```php
try {
    $stmt = $this->db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
    $stmt->execute([$username, $email, $hashedPassword]);
    return $this->db->lastInsertId();
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        throw new ValidationException('Username or email already exists');
    }
    throw $e;
}
```

**Reference:** `models/User.php`

### exit

Stops script execution and can output a message.

**Example from `config/database.php`:**
```php
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Service unavailable: database connection failed.');
}
```

The message the visitor sees is generic; the PDO detail goes to the error log. `die()` with a raw exception message used to print the host, database name and driver into the browser.

---

## 10. HTTP Features

### header()

Send raw HTTP headers to the browser.

**Example from `controllers/AuthController.php`:**
```php
header('Location: /index.php?controller=task&action=index');
exit;
```

Common uses:
- Redirect: `header('Location: /path')`
- Set content type: `header('Content-Type: application/json')`

### exit

Stops script execution immediately.

**Example from `controllers/TaskController.php`:**
```php
header('Location: /index.php?controller=task&action=index');
exit;
```

Always use `exit` after `header('Location: ...')` to prevent further code execution.

### Request Methods

Check the HTTP method used for the request.

**Example from `controllers/AuthController.php`:**
```php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require __DIR__ . '/../views/auth/login.php';
    return;
}
```

---

## 11. Session Management

### session_start()

Start a new or resume existing session. Must be called before any output.

**Example from `index.php`:**
```php
require_once __DIR__ . '/config/session.php';

session_start();
```

`config/session.php` sets the cookie flags before the session starts; `session_set_cookie_params()` only affects the session that starts afterwards.

### session_destroy()

Destroys all session data.

**Example from `controllers/AuthController.php`:**
```php
public function logout() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Allow: POST');
        exit('Method Not Allowed');
    }

    csrf_require();

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);
    }

    session_destroy();

    header('Location: /index.php?controller=auth&action=login');
    exit;
}
```

`session_destroy()` alone removes the server-side record but leaves the cookie in the browser and `$_SESSION` populated for the rest of the request, so a full logout also empties the array and expires the cookie.

### Storing Session Data

Set values in the `$_SESSION` superglobal.

**Example from `controllers/AuthController.php`:**
```php
$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
```

### Accessing Session Data

Read values from `$_SESSION`.

**Example from `controllers/TaskController.php`:**
```php
$userId = $_SESSION['user_id'];
$statusFilter = status_filter($_GET['status'] ?? null);
$tasks = $this->task->getByUserId($userId, $statusFilter);
```

The session is the only place the current user id comes from, so a visitor cannot ask for another user's tasks by changing a request parameter.

---

## 12. Database Operations (PDO)

### PDO Connection

Connect to a database using PHP Data Objects (PDO).

**Example from `config/database.php`:**
```php
$host = self::env('DB_HOST');
$name = self::env('DB_NAME');
$user = self::env('DB_USER');
$password = self::env('DB_PASSWORD');

$dsn = "mysql:host=$host;dbname=$name;charset=utf8mb4";
$this->connection = new PDO($dsn, $user, $password);
```

DSN (Data Source Name) format: `mysql:host=hostname;dbname=database_name;charset=utf8mb4`

### PDO Attributes

Configure PDO behavior.

**Example from `config/database.php`:**
```php
$this->connection->setAttribute(
    PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION,
);
$this->connection->setAttribute(
    PDO::ATTR_DEFAULT_FETCH_MODE,
    PDO::FETCH_ASSOC,
);
```

- `PDO::ERRMODE_EXCEPTION` - Throw exceptions on errors
- `PDO::FETCH_ASSOC` - Return arrays with column names as keys

### Prepared Statements

Prevent SQL injection by separating SQL from data.

**Example from `models/User.php`:**
```php
$stmt = $this->db->prepare('INSERT INTO users (username, email, password) VALUES (?, ?, ?)');
```

The `?` are placeholders that will be replaced with actual values.

### Executing Prepared Statements

Pass values to placeholders and execute the query.

**Example from `models/User.php`:**
```php
$stmt->execute([$username, $email, $hashedPassword]);
```

### Fetching Data

**Fetch single row:**
```php
$user = $stmt->fetch();
```
**Reference:** `models/User.php`

**Fetch all rows:**
```php
$tasks = $stmt->fetchAll();
```
**Reference:** `models/Task.php`

### lastInsertId()

Get the ID of the last inserted row.

**Example from `models/User.php`:**
```php
return $this->db->lastInsertId();
```

### rowCount()

Return the number of affected rows.

**Example from `models/Task.php`:**
```php
return $stmt->rowCount() > 0;
```

---

## 13. Security Functions

### password_hash()

Create a secure password hash.

**Example from `models/User.php`:**
```php
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
```

**Reference:** `models/User.php`

`PASSWORD_DEFAULT` uses the bcrypt algorithm (currently the strongest).

### password_verify()

Verify a password against its hash.

**Example from `models/User.php`:**
```php
if ($user && password_verify($password, $user['password'])) {
    unset($user['password']);
    return $user;
}
```

**Reference:** `models/User.php`

### htmlspecialchars()

Convert special characters to HTML entities to prevent XSS (Cross-Site Scripting) attacks.

**Example from `views/task/index.php`:**
```php
<h2><?php echo htmlspecialchars($task['title']); ?></h2>
```

**Reference:** `views/task/index.php`

**Example from `views/task/index.php`:**
```php
<p><?php echo htmlspecialchars($task['description']); ?></p>
```

### SQL Injection Prevention

Always use prepared statements with PDO.

**Secure (✓):**
```php
$stmt = $this->db->prepare('SELECT * FROM users WHERE username = ?');
$stmt->execute([$username]);
```

**Insecure (✗):**
```php
$query = "SELECT * FROM users WHERE username = '$username'"; // DON'T DO THIS!
```

---

## 14. String & Array Manipulation

### String Functions

**`ucfirst()`** - Capitalize first letter
```php
<?php echo ucfirst(str_replace('_', ' ', $task['status'])); ?>
```
**Reference:** `views/task/index.php`

**`str_replace()`** - Replace all occurrences of a string
```php
str_replace('_', ' ', 'in_progress') // Returns 'in progress'
```
**Reference:** `views/task/index.php`

**`strtotime()`** - Convert English textual date/time to Unix timestamp
```php
strtotime($task['created_at'])
```
**Reference:** `views/task/index.php`

### Array Functions

**Adding to array:**
```php
$errors = [];
$errors[] = 'Username is required';
```
**Reference:** `controllers/AuthController.php`

**Accessing array elements:**
```php
$task['title']
$task['status']
```
**Reference:** `views/task/index.php`

**Unsetting array elements:**
```php
unset($user['password']);
```
**Reference:** `models/User.php`

**Checking if array is empty:**
```php
if (empty($tasks)):
```
**Reference:** `views/task/index.php`

---

## Quick Reference

### File Paths Referenced

- `index.php` - Main entry point with routing
- `config/database.php` - Database connection (singleton pattern)
- `models/User.php` - User model (authentication)
- `models/Task.php` - Task model (CRUD operations)
- `controllers/AuthController.php` - Authentication controller
- `controllers/TaskController.php` - Task controller
- `views/auth/login.php` - Login form
- `views/auth/register.php` - Registration form
- `views/task/index.php` - Task list view
- `views/task/create.php` - Create task form
- `views/task/edit.php` - Edit task form
- `views/task/delete.php` - Delete task confirmation
- `views/layout.php` - Main layout template

### Common Patterns

1. **Singleton Pattern** - `config/database.php`
2. **MVC Pattern** - Models, Views, Controllers
3. **Dependency Injection** - Passing database connection to models
4. **Input Validation** - Always validate user input
5. **Security** - Use prepared statements, escape output, hash passwords

### Best Practices

1. Always use `require_once` for including files
2. Validate and sanitize all user input
3. Use prepared statements to prevent SQL injection
4. Escape output with `htmlspecialchars()` to prevent XSS
5. Hash passwords with `password_hash()`
6. Check `$_SERVER['REQUEST_METHOD']` for form submissions
7. Use `exit` after `header('Location: ...')`
8. Use `??` null coalescing for optional parameters
9. Organize code with classes and methods
10. Keep views free of business logic

---

## Next Steps

Now that you understand these PHP concepts, you can:

1. Read through the codebase and understand how each concept is used
2. Modify existing features
3. Add new features following the established patterns
4. Practice by creating small examples for each concept

Happy coding! 🚀
