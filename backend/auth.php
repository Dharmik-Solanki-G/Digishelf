<?php
/*
 * AUTHENTICATION API
 * Purpose: User/Admin login and registration
 * Save this as: C:\xampp\htdocs\digishelf\backend\auth.php
 * 
 * This file handles:
 * - User login/registration
 * - Admin login
 * - Password verification
 * - Session management
 */

require_once 'config.php';

// Get request method and input data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Route requests based on method
switch ($method) {
    case 'POST':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'login':
                handleUserLogin($pdo, $input);
                break;
            case 'register':
                handleUserRegister($pdo, $input);
                break;
            case 'admin-login':
                handleAdminLogin($pdo, $input);
                break;
            case 'logout':
                handleLogout();
                break;
            default:
                sendError('Invalid action. Supported: login, register, admin-login, logout');
        }
        break;
        
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'verify-token':
                verifyUserToken($pdo);
                break;
            default:
                sendError('Invalid GET action', 405);
        }
        break;
        
    default:
        sendError('Method not allowed. Use POST for authentication.', 405);
}

/**
 * Handle User Login
 */
function handleUserLogin($pdo, $data) {
    // Validate required fields
    validateInput($data, ['email', 'password']);
    
    // Sanitize inputs
    $email = sanitizeInput($data['email']);
    $password = $data['password'];
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    try {
        // Find user by email
        $stmt = $pdo->prepare("
            SELECT id, student_id, name, email, password, course, year, status, created_at 
            FROM users 
            WHERE email = ? AND status = 'active'
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Check if user exists and password is correct
        if (!$user || !verifyPassword($password, $user['password'])) {
            logActivity($pdo, null, 'failed_login', "Email: $email");
            sendError('Invalid email or password', 401);
        }
        
        // Check if account is blocked
        if ($user['status'] === 'blocked') {
            logActivity($pdo, $user['id'], 'blocked_login_attempt', '');
            sendError('Your account has been blocked. Please contact administrator.', 403);
        }
        
        // Remove password from response
        unset($user['password']);
        
        // Generate simple token (in production, use JWT)
        $token = 'user_' . $user['id'] . '_' . time();
        
        // Log successful login
        logActivity($pdo, $user['id'], 'user_login', 'Successful login');
        
        // Send success response
        sendResponse([
            'message' => 'Login successful',
            'user' => $user,
            'token' => $token,
            'redirect' => 'user-dashboard.html'
        ]);
        
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        sendError('Login failed. Please try again.', 500);
    }
}

/**
 * Handle User Registration
 */
function handleUserRegister($pdo, $data) {
    // Validate required fields
    validateInput($data, ['student_id', 'name', 'email', 'password', 'course', 'year']);
    
    // Sanitize inputs
    $studentId = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];
    $course = sanitizeInput($data['course']);
    $year = sanitizeInput($data['year']);
    $phone = isset($data['phone']) ? sanitizeInput($data['phone']) : null;
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    // Validate password strength (optional)
    if (strlen($password) < 6) {
        sendError('Password must be at least 6 characters long');
    }
    
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE email = ? OR student_id = ?
        ");
        $stmt->execute([$email, $studentId]);
        
        if ($stmt->fetch()) {
            sendError('User with this email or student ID already exists');
        }
        
        // Hash password
        $hashedPassword = hashPassword($password);
        
        // Insert new user
        $stmt = $pdo->prepare("
            INSERT INTO users (student_id, name, email, password, phone, course, year, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            $studentId,
            $name,
            $email,
            $hashedPassword,
            $phone,
            $course,
            $year
        ]);
        
        if ($result) {
            $newUserId = $pdo->lastInsertId();
            logActivity($pdo, $newUserId, 'user_registration', "New user registered: $name");
            
            sendResponse([
                'message' => 'Registration successful! You can now login.',
                'user_id' => $newUserId
            ], 201);
        } else {
            sendError('Registration failed. Please try again.');
        }
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        
        // Check for duplicate key error
        if ($e->getCode() == 23000) {
            sendError('User with this email or student ID already exists');
        } else {
            sendError('Registration failed. Please try again.');
        }
    }
}

/**
 * Handle Admin Login
 */
function handleAdminLogin($pdo, $data) {
    // Validate required fields
    validateInput($data, ['username', 'password']);
    
    // Sanitize inputs
    $username = sanitizeInput($data['username']);
    $password = $data['password'];
    
    try {
        // Find admin by username
        $stmt = $pdo->prepare("
            SELECT id, username, name, email, password, created_at 
            FROM admins 
            WHERE username = ?
        ");
        $stmt->execute([$username]);
        $admin = $stmt->fetch();
        
        // Check credentials
        if (!$admin || !verifyPassword($password, $admin['password'])) {
            logActivity($pdo, null, 'failed_admin_login', "Username: $username");
            sendError('Invalid username or password', 401);
        }
        
        // Remove password from response
        unset($admin['password']);
        
        // Generate admin token
        $token = 'admin_' . $admin['id'] . '_' . time();
        
        // Log successful admin login
        logActivity($pdo, $admin['id'], 'admin_login', 'Admin logged in');
        
        // Send success response
        sendResponse([
            'message' => 'Admin login successful',
            'admin' => $admin,
            'token' => $token,
            'redirect' => 'admin-dashboard.html'
        ]);
        
    } catch (PDOException $e) {
        error_log("Admin login error: " . $e->getMessage());
        sendError('Login failed. Please try again.', 500);
    }
}

/**
 * Handle Logout
 */
function handleLogout() {
    // In a real application, you would:
    // 1. Invalidate the token
    // 2. Clear server-side sessions
    // 3. Log the logout activity
    
    sendResponse([
        'message' => 'Logout successful',
        'redirect' => '../index.html'
    ]);
}

/**
 * Verify Token (for protected routes)
 */
function verifyUserToken($pdo) {
    $token = $_GET['token'] ?? '';
    
    if (empty($token)) {
        sendError('Token required', 401);
    }
    
    // Simple token validation (in production, use JWT)
    if (strpos($token, 'user_') === 0) {
        $parts = explode('_', $token);
        if (count($parts) >= 3) {
            $userId = $parts[1];
            
            // Verify user exists and is active
            $stmt = $pdo->prepare("
                SELECT id, name, email, status 
                FROM users 
                WHERE id = ? AND status = 'active'
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            
            if ($user) {
                sendResponse([
                    'valid' => true,
                    'user' => $user
                ]);
            }
        }
    }
    
    if (strpos($token, 'admin_') === 0) {
        $parts = explode('_', $token);
        if (count($parts) >= 3) {
            $adminId = $parts[1];
            
            // Verify admin exists
            $stmt = $pdo->prepare("
                SELECT id, username, name, email 
                FROM admins 
                WHERE id = ?
            ");
            $stmt->execute([$adminId]);
            $admin = $stmt->fetch();
            
            if ($admin) {
                sendResponse([
                    'valid' => true,
                    'admin' => $admin
                ]);
            }
        }
    }
    
    sendError('Invalid or expired token', 401);
}

// Create activity logs table if it doesn't exist
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS activity_logs (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NULL,
            action VARCHAR(100) NOT NULL,
            details TEXT,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_action (action),
            INDEX idx_created_at (created_at)
        )
    ");
} catch (PDOException $e) {
    error_log("Failed to create activity_logs table: " . $e->getMessage());
}
?>