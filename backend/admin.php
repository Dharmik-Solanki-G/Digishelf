<?php
/*
 * ADMIN MANAGEMENT API
 * Purpose: Admin panel & management
 * Save this as: C:\xampp\htdocs\digishelf\backend\admin.php
 * 
 * This file handles:
 * - Admin dashboard statistics
 * - User management (block/unblock)
 * - Book request approvals
 * - Transaction management
 * - Reports generation
 */

/*
 * ENHANCED ADMIN API
 * Save as: enhanced_admin.php (or update your existing admin.php)
 * Includes: Analytics, Member Management, Book Management with PDF upload
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Handle file uploads for book PDFs and covers
if (isset($_FILES) && !empty($_FILES)) {
    $input = array_merge($input ?: [], $_POST);
}

switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        handleGetRequests($pdo, $action);
        break;
        
    case 'POST':
        $action = $_GET['action'] ?? '';
        handlePostRequests($pdo, $action, $input);
        break;
        
    case 'PUT':
        $action = $_GET['action'] ?? '';
        handlePutRequests($pdo, $action, $input);
        break;
        
    case 'DELETE':
        $action = $_GET['action'] ?? '';
        handleDeleteRequests($pdo, $action);
        break;
        
    default:
        sendError('Method not allowed', 405);
}

function handleGetRequests($pdo, $action) {
    switch ($action) {
        case 'dashboard-analytics':
            getAdvancedAnalytics($pdo);
            break;
        case 'members':
            getAllMembers($pdo);
            break;
        case 'member-details':
            getMemberDetails($pdo, $_GET['member_id'] ?? null);
            break;
        case 'books-management':
            getBooksForManagement($pdo);
            break;
        case 'analytics-charts':
            getAnalyticsCharts($pdo);
            break;
        case 'system-stats':
            getSystemStatistics($pdo);
            break;
        case 'export-data':
            exportData($pdo, $_GET['type'] ?? 'users');
            break;
        default:
            sendError('Invalid action');
    }
}

function handlePostRequests($pdo, $action, $input) {
    switch ($action) {
        case 'add-member':
            addNewMember($pdo, $input);
            break;
        case 'add-book':
            addBookWithFiles($pdo, $input);
            break;
        case 'send-notification':
            sendBulkNotification($pdo, $input);
            break;
        case 'generate-report':
            generateCustomReport($pdo, $input);
            break;
        default:
            sendError('Invalid action');
    }
}

function handlePutRequests($pdo, $action, $input) {
    switch ($action) {
        case 'update-member':
            updateMember($pdo, $input);
            break;
        case 'update-book':
            updateBookWithFiles($pdo, $input);
            break;
        default:
            sendError('Invalid action');
    }
}

function handleDeleteRequests($pdo, $action) {
    switch ($action) {
        case 'delete-member':
            deleteMember($pdo, $_GET['member_id'] ?? null);
            break;
        case 'delete-book':
            deleteBookPermanently($pdo, $_GET['book_id'] ?? null);
            break;
        default:
            sendError('Invalid action');
    }
}

/**
 * Advanced Analytics Dashboard
 */
function getAdvancedAnalytics($pdo) {
    try {
        // Update analytics first
        $pdo->exec("CALL UpdateDailyAnalytics()");
        
        // Basic stats
        $basicStats = getBasicStatistics($pdo);
        
        // User growth data (last 12 months)
        $userGrowth = getUserGrowthData($pdo);
        
        // Book circulation data
        $circulation = getCirculationData($pdo);
        
        // Popular books
        $popularBooks = getPopularBooks($pdo);
        
        // Category distribution
        $categoryDistribution = getCategoryDistribution($pdo);
        
        // Recent activities
        $recentActivities = getRecentActivities($pdo);
        
        // Performance metrics
        $performance = getPerformanceMetrics($pdo);
        
        sendResponse([
            'basic_stats' => $basicStats,
            'user_growth' => $userGrowth,
            'circulation' => $circulation,
            'popular_books' => $popularBooks,
            'category_distribution' => $categoryDistribution,
            'recent_activities' => $recentActivities,
            'performance' => $performance,
            'generated_at' => date('Y-m-d H:i:s')
        ]);
        
    } catch (PDOException $e) {
        error_log("Advanced analytics error: " . $e->getMessage());
        sendError('Failed to generate analytics');
    }
}

function getBasicStatistics($pdo) {
    $stmt = $pdo->query("SELECT * FROM dashboard_stats LIMIT 1");
    return $stmt->fetch();
}

function getUserGrowthData($pdo) {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as new_users,
            SUM(COUNT(*)) OVER (ORDER BY DATE_FORMAT(created_at, '%Y-%m')) as total_users
        FROM users
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    return $stmt->fetchAll();
}

function getCirculationData($pdo) {
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            COUNT(*) as books_borrowed,
            COUNT(CASE WHEN status = 'returned' THEN 1 END) as books_returned,
            COUNT(CASE WHEN status = 'issued' AND due_date < CURDATE() THEN 1 END) as overdue_books
        FROM transactions
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month
    ");
    return $stmt->fetchAll();
}

function getPopularBooks($pdo) {
    $stmt = $pdo->query("
        SELECT 
            b.title,
            b.author,
            c.name as category,
            COUNT(t.id) as borrow_count,
            COALESCE(AVG(r.rating), 0) as avg_rating,
            b.available_quantity
        FROM books b
        LEFT JOIN transactions t ON b.id = t.book_id
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN reviews r ON b.id = r.book_id
        WHERE b.status = 'active'
        GROUP BY b.id
        ORDER BY borrow_count DESC
        LIMIT 10
    ");
    return $stmt->fetchAll();
}

function getCategoryDistribution($pdo) {
    $stmt = $pdo->query("
        SELECT 
            c.name as category,
            COUNT(b.id) as book_count,
            COUNT(t.id) as total_borrows,
            COUNT(CASE WHEN t.status = 'issued' THEN 1 END) as currently_borrowed
        FROM categories c
        LEFT JOIN books b ON c.id = b.category_id AND b.status = 'active'
        LEFT JOIN transactions t ON b.id = t.book_id
        GROUP BY c.id, c.name
        HAVING book_count > 0
        ORDER BY book_count DESC
    ");
    return $stmt->fetchAll();
}

function getRecentActivities($pdo) {
    $stmt = $pdo->query("
        SELECT 
            al.created_at,
            al.action,
            al.details,
            u.name as user_name,
            u.student_id
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id
        ORDER BY al.created_at DESC
        LIMIT 20
    ");
    return $stmt->fetchAll();
}

function getPerformanceMetrics($pdo) {
    $stmt = $pdo->query("
        SELECT 
            'average_rating' as metric,
            ROUND(AVG(rating), 2) as value
        FROM reviews WHERE status = 'approved'
        
        UNION ALL
        
        SELECT 
            'user_satisfaction' as metric,
            ROUND((COUNT(CASE WHEN rating >= 4 THEN 1 END) * 100.0 / COUNT(*)), 1) as value
        FROM reviews WHERE status = 'approved'
        
        UNION ALL
        
        SELECT 
            'book_availability' as metric,
            ROUND((SUM(available_quantity) * 100.0 / SUM(quantity)), 1) as value
        FROM books WHERE status = 'active'
        
        UNION ALL
        
        SELECT 
            'on_time_returns' as metric,
            ROUND((COUNT(CASE WHEN return_date <= due_date THEN 1 END) * 100.0 / COUNT(*)), 1) as value
        FROM transactions WHERE status = 'returned' AND return_date IS NOT NULL
    ");
    
    $metrics = [];
    while ($row = $stmt->fetch()) {
        $metrics[$row['metric']] = $row['value'];
    }
    
    return $metrics;
}

/**
 * Get All Members with Advanced Filtering
 */
function getAllMembers($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $status = $_GET['status'] ?? '';
        $course = $_GET['course'] ?? '';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT 
                u.*,
                COUNT(t.id) as total_borrowed,
                COUNT(CASE WHEN t.status = 'issued' THEN 1 END) as currently_borrowed,
                COUNT(CASE WHEN t.status = 'issued' AND t.due_date < CURDATE() THEN 1 END) as overdue_books,
                COALESCE(SUM(CASE WHEN t.fine_paid = FALSE THEN t.fine_amount END), 0) as outstanding_fines,
                MAX(al.created_at) as last_activity
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id
            LEFT JOIN activity_logs al ON u.id = al.user_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.student_id LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($status) {
            $sql .= " AND u.status = ?";
            $params[] = $status;
        }
        
        if ($course) {
            $sql .= " AND u.course LIKE ?";
            $params[] = "%$course%";
        }
        
        $sql .= " GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
        $params = array_merge($params, [$limit, $offset]);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $members = $stmt->fetchAll();
        
        // Get total count for pagination
        $countSql = str_replace(['COUNT(t.id) as total_borrowed', 'GROUP BY u.id ORDER BY u.created_at DESC LIMIT ? OFFSET ?'], ['COUNT(DISTINCT u.id) as total'], $sql);
        $countParams = array_slice($params, 0, -2); // Remove limit and offset
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalMembers = $countStmt->fetchColumn();
        
        sendResponse([
            'members' => $members,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalMembers / $limit),
                'total_members' => (int)$totalMembers,
                'per_page' => $limit
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Get members error: " . $e->getMessage());
        sendError('Failed to fetch members');
    }
}

/**
 * Get Member Details
 */
function getMemberDetails($pdo, $memberId) {
    if (!$memberId) {
        sendError('Member ID required');
    }
    
    try {
        // Get member info
        $stmt = $pdo->prepare("
            SELECT u.*, 
                   COUNT(t.id) as total_transactions,
                   COUNT(CASE WHEN t.status = 'issued' THEN 1 END) as currently_borrowed,
                   COUNT(r.id) as reviews_written,
                   MAX(al.created_at) as last_activity
            FROM users u
            LEFT JOIN transactions t ON u.id = t.user_id
            LEFT JOIN reviews r ON u.id = r.user_id
            LEFT JOIN activity_logs al ON u.id = al.user_id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch();
        
        if (!$member) {
            sendError('Member not found', 404);
        }
        
        // Get borrowing history
        $stmt = $pdo->prepare("
            SELECT t.*, b.title, b.author, c.name as category_name
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE t.user_id = ?
            ORDER BY t.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([$memberId]);
        $borrowingHistory = $stmt->fetchAll();
        
        // Get reading sessions
        $stmt = $pdo->prepare("
            SELECT rs.*, b.title
            FROM reading_sessions rs
            JOIN books b ON rs.book_id = b.id
            WHERE rs.user_id = ? AND rs.session_end IS NOT NULL
            ORDER BY rs.session_start DESC
            LIMIT 10
        ");
        $stmt->execute([$memberId]);
        $readingSessions = $stmt->fetchAll();
        
        // Get reviews
        $stmt = $pdo->prepare("
            SELECT r.*, b.title, b.author
            FROM reviews r
            JOIN books b ON r.book_id = b.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $stmt->execute([$memberId]);
        $reviews = $stmt->fetchAll();
        
        sendResponse([
            'member' => $member,
            'borrowing_history' => $borrowingHistory,
            'reading_sessions' => $readingSessions,
            'reviews' => $reviews
        ]);
        
    } catch (PDOException $e) {
        error_log("Get member details error: " . $e->getMessage());
        sendError('Failed to fetch member details');
    }
}

/**
 * Add New Member
 */
function addNewMember($pdo, $data) {
    validateInput($data, ['student_id', 'name', 'email', 'course', 'year']);
    
    $studentId = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $course = sanitizeInput($data['course']);
    $year = sanitizeInput($data['year']);
    $phone = sanitizeInput($data['phone'] ?? '');
    $password = $data['password'] ?? 'password123'; // Default password
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        sendError('Invalid email format');
    }
    
    try {
        // Check for existing user
        $stmt = $pdo->prepare("
            SELECT id FROM users 
            WHERE email = ? OR student_id = ?
        ");
        $stmt->execute([$email, $studentId]);
        
        if ($stmt->fetch()) {
            sendError('User with this email or student ID already exists');
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert new member
        $stmt = $pdo->prepare("
            INSERT INTO users (student_id, name, email, password, phone, course, year, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            $studentId, $name, $email, $hashedPassword, $phone, $course, $year
        ]);
        
        if ($result) {
            $newUserId = $pdo->lastInsertId();
            
            // Create welcome notification
            createNotification(
                $pdo, 
                $newUserId, 
                'Welcome to DigiShelf!', 
                'Your account has been created by an administrator. Please change your password on first login.',
                'info'
            );
            
            logActivity($pdo, null, 'member_added', "New member added: $name (ID: $studentId)");
            
            sendResponse([
                'message' => 'Member added successfully',
                'member_id' => $newUserId,
                'temporary_password' => $password
            ], 201);
        } else {
            sendError('Failed to add member');
        }
        
    } catch (PDOException $e) {
        error_log("Add member error: " . $e->getMessage());
        sendError('Failed to add member');
    }
}

/**
 * Update Member
 */
function updateMember($pdo, $data) {
    validateInput($data, ['member_id']);
    
    $memberId = (int)$data['member_id'];
    $name = sanitizeInput($data['name'] ?? '');
    $phone = sanitizeInput($data['phone'] ?? '');
    $course = sanitizeInput($data['course'] ?? '');
    $year = sanitizeInput($data['year'] ?? '');
    $status = sanitizeInput($data['status'] ?? '');
    
    try {
        $stmt = $pdo->prepare("
            UPDATE users 
            SET name = ?, phone = ?, course = ?, year = ?, status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$name, $phone, $course, $year, $status, $memberId]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($pdo, $memberId, 'member_updated', "Member updated by admin");
            sendResponse(['message' => 'Member updated successfully']);
        } else {
            sendError('Member not found or no changes made');
        }
        
    } catch (PDOException $e) {
        error_log("Update member error: " . $e->getMessage());
        sendError('Failed to update member');
    }
}

/**
 * Add Book with File Upload
 */
function addBookWithFiles($pdo, $data) {
    validateInput($data, ['title', 'author', 'category_id']);
    
    $title = sanitizeInput($data['title']);
    $author = sanitizeInput($data['author']);
    $isbn = sanitizeInput($data['isbn'] ?? '');
    $categoryId = (int)$data['category_id'];
    $description = sanitizeInput($data['description'] ?? '');
    $publisher = sanitizeInput($data['publisher'] ?? '');
    $publicationYear = (int)($data['publication_year'] ?? null);
    $pages = (int)($data['pages'] ?? null);
    $quantity = (int)($data['quantity'] ?? 1);
    
    try {
        // Handle PDF upload
        $pdfFile = null;
        $pdfSize = null;
        if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
            $uploadResult = handlePDFUpload($_FILES['pdf_file']);
            $pdfFile = $uploadResult['filename'];
            $pdfSize = $uploadResult['size'];
        }
        
        // Handle cover image upload
        $coverImage = null;
        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $coverImage = handleCoverImageUpload($_FILES['cover_image']);
        }
        
        // Calculate reading time (rough estimate: 250 words per page, 200 words per minute)
        $readingTime = $pages ? ceil($pages * 250 / 200) : null;
        
        // Insert book
        $stmt = $pdo->prepare("
            INSERT INTO books (
                title, author, isbn, category_id, description, publisher, 
                publication_year, pages, quantity, available_quantity, 
                pdf_file, pdf_size, total_pages, reading_time_minutes, 
                cover_image, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            $title, $author, $isbn, $categoryId, $description, $publisher,
            $publicationYear, $pages, $quantity, $quantity,
            $pdfFile, $pdfSize, $pages, $readingTime,
            $coverImage
        ]);
        
        if ($result) {
            $bookId = $pdo->lastInsertId();
            logActivity($pdo, null, 'book_added', "New book added: $title");
            
            sendResponse([
                'message' => 'Book added successfully',
                'book_id' => $bookId,
                'pdf_uploaded' => $pdfFile !== null,
                'cover_uploaded' => $coverImage !== null
            ], 201);
        } else {
            sendError('Failed to add book');
        }
        
    } catch (Exception $e) {
        error_log("Add book error: " . $e->getMessage());
        sendError('Failed to add book: ' . $e->getMessage());
    }
}

/**
 * Handle PDF Upload
 */
function handlePDFUpload($file) {
    $allowedTypes = ['application/pdf'];
    $maxSize = 50 * 1024 * 1024; // 50MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only PDF files are allowed.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 50MB.');
    }
    
    $uploadDir = __DIR__ . '/uploads/books/pdfs/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $filename = 'book_' . time() . '_' . uniqid() . '.pdf';
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return [
            'filename' => 'uploads/books/pdfs/' . $filename,
            'size' => $file['size']
        ];
    } else {
        throw new Exception('Failed to upload PDF file.');
    }
}

/**
 * Handle Cover Image Upload
 */
function handleCoverImageUpload($file) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    
    $uploadDir = __DIR__ . '/uploads/books/covers/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'cover_' . time() . '_' . uniqid() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/books/covers/' . $filename;
    } else {
        throw new Exception('Failed to upload cover image.');
    }
}

/**
 * Get Books for Management
 */
function getBooksForManagement($pdo) {
    try {
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $status = $_GET['status'] ?? 'active';
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = ($page - 1) * $limit;
        
        $sql = "
            SELECT 
                b.*,
                c.name as category_name,
                COUNT(t.id) as total_borrows,
                COUNT(CASE WHEN t.status = 'issued' THEN 1 END) as currently_borrowed,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN transactions t ON b.id = t.book_id
            LEFT JOIN reviews r ON b.id = r.book_id
            WHERE 1=1
        ";
        
        $params = [];
        
        if ($search) {
            $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
            $searchTerm = "%$search%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($category) {
            $sql .= " AND b.category_id = ?";
            $params[] = $category;
        }
        
        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }
        
        $sql .= " GROUP BY b.id ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $params = array_merge($params, [$limit, $offset]);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll();
        
        // Get total count
        $countSql = "SELECT COUNT(DISTINCT b.id) FROM books b LEFT JOIN categories c ON b.category_id = c.id WHERE 1=1";
        $countParams = [];
        
        if ($search) {
            $countSql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.isbn LIKE ?)";
            $searchTerm = "%$search%";
            $countParams = [$searchTerm, $searchTerm, $searchTerm];
        }
        
        if ($category) {
            $countSql .= " AND b.category_id = ?";
            $countParams[] = $category;
        }
        
        if ($status) {
            $countSql .= " AND b.status = ?";
            $countParams[] = $status;
        }
        
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($countParams);
        $totalBooks = $countStmt->fetchColumn();
        
        sendResponse([
            'books' => $books,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalBooks / $limit),
                'total_books' => (int)$totalBooks,
                'per_page' => $limit
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Get books for management error: " . $e->getMessage());
        sendError('Failed to fetch books');
    }
}

/**
 * Send Bulk Notification
 */
function sendBulkNotification($pdo, $data) {
    validateInput($data, ['title', 'message', 'recipients']);
    
    $title = sanitizeInput($data['title']);
    $message = sanitizeInput($data['message']);
    $type = sanitizeInput($data['type'] ?? 'info');
    $recipients = $data['recipients']; // 'all', 'active', 'overdue', or array of user IDs
    
    try {
        // Get recipient user IDs
        $userIds = [];
        
        if ($recipients === 'all') {
            $stmt = $pdo->query("SELECT id FROM users");
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($recipients === 'active') {
            $stmt = $pdo->query("SELECT id FROM users WHERE status = 'active'");
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif ($recipients === 'overdue') {
            $stmt = $pdo->query("
                SELECT DISTINCT t.user_id FROM transactions t
                WHERE t.status = 'issued' AND t.due_date < CURDATE()
            ");
            $userIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } elseif (is_array($recipients)) {
            $userIds = $recipients;
        }
        
        // Send notifications
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES (?, ?, ?, ?)
        ");
        
        $successCount = 0;
        foreach ($userIds as $userId) {
            if ($stmt->execute([$userId, $title, $message, $type])) {
                $successCount++;
            }
        }
        
        logActivity($pdo, null, 'bulk_notification_sent', "Sent to $successCount users: $title");
        
        sendResponse([
            'message' => 'Bulk notification sent successfully',
            'recipients_count' => $successCount
        ]);
        
    } catch (PDOException $e) {
        error_log("Send bulk notification error: " . $e->getMessage());
        sendError('Failed to send notifications');
    }
}

/**
 * Generate Custom Report
 */
function generateCustomReport($pdo, $data) {
    validateInput($data, ['report_type']);
    
    $reportType = sanitizeInput($data['report_type']);
    $startDate = $data['start_date'] ?? date('Y-m-01'); // First day of current month
    $endDate = $data['end_date'] ?? date('Y-m-d'); // Today
    $format = $data['format'] ?? 'json'; // json, csv, pdf
    
    try {
        $reportData = [];
        
        switch ($reportType) {
            case 'user_activity':
                $reportData = generateUserActivityReport($pdo, $startDate, $endDate);
                break;
            case 'book_circulation':
                $reportData = generateCirculationReport($pdo, $startDate, $endDate);
                break;
            case 'overdue_analysis':
                $reportData = generateOverdueReport($pdo, $startDate, $endDate);
                break;
            case 'reading_trends':
                $reportData = generateReadingTrendsReport($pdo, $startDate, $endDate);
                break;
            default:
                sendError('Invalid report type');
        }
        
        if ($format === 'csv') {
            exportToCSV($reportData, $reportType);
        } else {
            sendResponse([
                'report_type' => $reportType,
                'period' => "$startDate to $endDate",
                'generated_at' => date('Y-m-d H:i:s'),
                'data' => $reportData
            ]);
        }
        
    } catch (PDOException $e) {
        error_log("Generate report error: " . $e->getMessage());
        sendError('Failed to generate report');
    }
}

function generateUserActivityReport($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            u.name,
            u.student_id,
            u.course,
            COUNT(DISTINCT t.id) as books_borrowed,
            COUNT(DISTINCT rs.id) as reading_sessions,
            SUM(TIMESTAMPDIFF(MINUTE, rs.session_start, rs.session_end)) as total_reading_minutes,
            COUNT(DISTINCT r.id) as reviews_written,
            MAX(al.created_at) as last_activity
        FROM users u
        LEFT JOIN transactions t ON u.id = t.user_id AND DATE(t.created_at) BETWEEN ? AND ?
        LEFT JOIN reading_sessions rs ON u.id = rs.user_id AND DATE(rs.session_start) BETWEEN ? AND ?
        LEFT JOIN reviews r ON u.id = r.user_id AND DATE(r.created_at) BETWEEN ? AND ?
        LEFT JOIN activity_logs al ON u.id = al.user_id AND DATE(al.created_at) BETWEEN ? AND ?
        WHERE u.status = 'active'
        GROUP BY u.id
        ORDER BY books_borrowed DESC
    ");
    $stmt->execute([$startDate, $endDate, $startDate, $endDate, $startDate, $endDate, $startDate, $endDate]);
    return $stmt->fetchAll();
}

function generateCirculationReport($pdo, $startDate, $endDate) {
    $stmt = $pdo->prepare("
        SELECT 
            b.title,
            b.author,
            c.name as category,
            COUNT(t.id) as times_borrowed,
            COUNT(CASE WHEN t.status = 'returned' THEN 1 END) as times_returned,
            COUNT(CASE WHEN t.status = 'issued' AND t.due_date < CURDATE() THEN 1 END) as currently_overdue,
            AVG(CASE WHEN t.return_date THEN DATEDIFF(t.return_date, t.issue_date) END) as avg_loan_days
        FROM books b
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN transactions t ON b.id = t.book_id AND DATE(t.created_at) BETWEEN ? AND ?
        WHERE b.status = 'active'
        GROUP BY b.id
        HAVING times_borrowed > 0
        ORDER BY times_borrowed DESC
    ");
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

/**
 * Helper function to create notifications (if not exists)
 */
function createNotification($pdo, $userId, $title, $message, $type = 'info', $actionUrl = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, action_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $title, $message, $type, $actionUrl]);
    } catch (PDOException $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}

function exportToCSV($data, $filename) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    if (!empty($data)) {
        // Write headers
        fputcsv($output, array_keys($data[0]));
        
        // Write data
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
    }
    
    fclose($output);
    exit;
}
?>