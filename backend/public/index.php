<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
ob_start(); // Prevent any warnings/notices from breaking JSON output

session_start();

require __DIR__ . '/../src/autoload.php';

use KCS\Core\Router;
use KCS\Core\Response;
use KCS\Controllers\AuthController;
use KCS\Controllers\ClearanceController;
use KCS\Controllers\DashboardController;
use KCS\Controllers\FeedbackController;
use KCS\Controllers\AdminController;

$router = new Router();

// Auth
$router->add('POST', '/api/auth/register', [AuthController::class, 'register']);
$router->add('POST', '/api/auth/login', [AuthController::class, 'login']);
$router->add('POST', '/api/auth/logout', [AuthController::class, 'logout']);
$router->add('GET', '/api/auth/me', [AuthController::class, 'me']);
$router->add('GET', '/api/auth/csrf', [AuthController::class, 'csrf']);

// Student Clearance
$router->add('POST', '/api/student/request-clearance', [ClearanceController::class, 'requestClearance']);
$router->add('GET', '/api/student/clearance-status', [ClearanceController::class, 'myClearanceStatus']);

// Department Staff Decision
$router->add('POST', '/api/department/decision', [ClearanceController::class, 'departmentDecision']);
$router->add('GET', '/api/department/students-progress', [DashboardController::class, 'departmentStudentsProgress']);

// Admin Dashboards & Feedback
$router->add('GET', '/api/admin/dashboard-progress', [DashboardController::class, 'adminProgressByDepartment']);
$router->add('GET', '/api/admin/feedback-analytics', [FeedbackController::class, 'adminAnalytics']);

// Feedback submission
$router->add('POST', '/api/feedback/submit', [FeedbackController::class, 'submit']);

// Admin staff / configuration
$router->add('GET',  '/api/admin/departments', [AdminController::class, 'listDepartments']);
$router->add('POST', '/api/admin/departments/create', [AdminController::class, 'createDepartment']);
$router->add('POST', '/api/admin/create-department-staff', [AdminController::class, 'createDepartmentStaff']);
$router->add('POST', '/api/admin/students/create', [AdminController::class, 'createStudent']);
$router->add('POST', '/api/admin/financial/set-current', [AdminController::class, 'setCurrentFinancialRecord']);
$router->add('GET',  '/api/admin/students', [AdminController::class, 'listStudents']);
$router->add('POST', '/api/admin/financial/update-balance', [AdminController::class, 'updateBalance']);

// Student Dashboard & Certificate
$router->add('GET', '/api/student/dashboard', [DashboardController::class, 'studentDashboard']);
$router->add('GET', '/api/student/clearance-certificate', [ClearanceController::class, 'clearanceCertificate']);

try {
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  $uri = $_SERVER['REQUEST_URI'] ?? '/';
  $router->dispatch($method, $uri);
} catch (\Throwable $e) {
  Response::json(['ok' => false, 'error' => 'Server error'], 500);
}

