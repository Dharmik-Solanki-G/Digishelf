<?php
/*
 * DATABASE CONFIGURATION FILE
 * Purpose: Database connection & helper functions
 * Save this as: C:\xampp\htdocs\digishelf\backend\config.php
 * 
 * This file handles:
 * - Database connection
 * - CORS headers for frontend
 * - Helper functions
 * - Error handling
 */

// Database configuration - Update these if needed
define('DB_HOST', 'localhost');        // MySQL server host
define('DB_USER', 'root');             // MySQL username (default for XAMPP)
define('DB_PASS', '');                 // MySQL password (empty for XAMPP)
define('DB_NAME', 'digishelf_db');     // Your database name

// Enable CORS (Cross-Origin Resource Sharing) for frontend communication
header('Access-Control-Allow-Origin: *');  // Allow all origins (for development)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json');  // Always return JSON responses

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit(0);
}

/**
 * Database Connection Class
 * Handles MySQL connection using PDO
 */
class Database {
    private $connection;
    
    public function __construct() {
        try {
            // Create PDO connection
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,  // Throw exceptions on errors
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Return associative arrays
                PDO::ATTR_EMULATE_PREPARES => false  // Use real prepared statements
            ]);
        } catch (PDOException $e) {
            // Return error if connection fails
            http_response_code(500);
            die(json_encode([
                'error' => 'Database connection failed',
                'message' => 'Please check if MySQL is running in XAMPP'
            ]));
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
}

/**
 * Helper Functions for API responses
 */

// Send successful response
function sendResponse($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit();
}

// Send error response
function sendError($message, $status = 400) {
    http_response_code($status);
    echo json_encode([
        'error' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

// Validate required input fields
function validateInput($data, $required_fields) {
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty(trim($data[$field]))) {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        sendError("Missing required fields: " . implode(', ', $missing_fields), 422);
    }
}

// Hash password securely
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Sanitize input to prevent XSS
function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// Log activity for security
function logActivity($pdo, $userId, $action, $details = '') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $userId,
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (PDOException $e) {
        // Silently fail if logging fails
        error_log("Activity logging failed: " . $e->getMessage());
    }
}

// Create database connection instance
try {
    $db = new Database();
    $pdo = $db->getConnection();
} catch (Exception $e) {
    sendError('Server configuration error', 500);
}

// Enable error reporting for development (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
?>