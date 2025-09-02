<?php
/*
 * USER MANAGEMENT API
 * Purpose: User dashboard and profile
 * Save this as: C:\xampp\htdocs\digishelf\backend\user.php
 * 
 * This file handles:
 * - User dashboard data
 * - Book requests
 * - Borrowing history
 * - User profile updates
 */

/*
 * ENHANCED USER API
 * Save as: enhanced_user.php (or update your existing user.php)
 * Includes: Dashboard, Reviews, Reading Sessions, Notifications, Wishlist
 */

require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Handle file uploads
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
    $userId = $_GET['user_id'] ?? null;
    
    switch ($action) {
        case 'dashboard':
            getEnhancedDashboard($pdo, $userId);
            break;
        case 'notifications':
            getUserNotifications($pdo, $userId);
            break;
        case 'reading-history':
            getReadingHistory($pdo, $userId);
            break;
        case 'wishlist':
            getUserWishlist($pdo, $userId);
            break;
        case 'reading-stats':
            getReadingStats($pdo, $userId);
            break;
        case 'recommendations':
            getBookRecommendations($pdo, $userId);
            break;
        default:
            sendError('Invalid action');
    }
}
function handlePostRequests($pdo, $action, $input) {
    switch ($action) {
        case 'add-review':
            addBookReview($pdo, $input);
            break;
        case 'start-reading':
            startReadingSession($pdo, $input);
            break;
        case 'end-reading':
            endReadingSession($pdo, $input);
            break;
        case 'add-to-wishlist':
            addToWishlist($pdo, $input);
            break;
        case 'mark-notification-read':
            markNotificationRead($pdo, $input);
            break;
        case 'request-book':
            requestBook($pdo, $input);
            break;
        case 'vote-review':
            voteOnReview($pdo, $input);
            break;
        default:
            sendError('Invalid action');
    }
}
function handlePutRequests($pdo, $action, $input) {
    switch ($action) {
        case 'update-reading-progress':
            updateReadingProgress($pdo, $input);
            break;
        case 'update-profile':
            updateUserProfile($pdo, $input);
            break;
        default:
            sendError('Invalid action');
    }
}
function handleDeleteRequests($pdo, $action) {
    switch ($action) {
        case 'remove-from-wishlist':
            removeFromWishlist($pdo, $_GET['user_id'], $_GET['book_id']);
            break;
        case 'delete-review':
            deleteUserReview($pdo, $_GET['review_id'], $_GET['user_id']);
            break;
        default:
            sendError('Invalid action');
    }
}
/**
 * Enhanced Dashboard with comprehensive user data
 */
function getEnhancedDashboard($pdo, $userId) {
    if (!$userId) {
        sendError('User ID required');
    }
    
    try {
        // Get user information
        $stmt = $pdo->prepare("
            SELECT id, student_id, name, email, course, year, status, created_at,
                   last_login, email_verified
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            sendError('User not found', 404);
        }
        
        // Get comprehensive statistics
        $stats = [
            'total_borrowed' => getTotalBorrowed($pdo, $userId),
            'currently_borrowed' => getCurrentlyBorrowed($pdo, $userId),
            'overdue_books' => getOverdueBooks($pdo, $userId),
            'total_fines' => getTotalFines($pdo, $userId),
            'books_read' => getBooksRead($pdo, $userId),
            'reading_hours' => getTotalReadingHours($pdo, $userId),
            'reviews_written' => getReviewsWritten($pdo, $userId),
            'wishlist_count' => getWishlistCount($pdo, $userId)
        ];
        
        // Get currently borrowed books with details
        $borrowedBooks = getCurrentBorrowedBooksDetailed($pdo, $userId);
        
        // Get recent reading activity
        $recentActivity = getRecentReadingActivity($pdo, $userId);
        
        // Get reading recommendations
        $recommendations = getBookRecommendations($pdo, $userId, 4);
        
        // Get unread notifications
        $notifications = getUnreadNotifications($pdo, $userId);
        
        // Get reading streak
        $readingStreak = getReadingStreak($pdo, $userId);
        
        // Get favorite genres
        $favoriteGenres = getFavoriteGenres($pdo, $userId);
        
        sendResponse([
            'user' => $user,
            'stats' => $stats,
            'borrowed_books' => $borrowedBooks,
            'recent_activity' => $recentActivity,
            'recommendations' => $recommendations,
            'notifications' => $notifications,
            'reading_streak' => $readingStreak,
            'favorite_genres' => $favoriteGenres
        ]);
        
    } catch (PDOException $e) {
        error_log("Enhanced dashboard error: " . $e->getMessage());
        sendError('Failed to load dashboard data');
    }
}
function getTotalBorrowed($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function getCurrentlyBorrowed($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM transactions 
        WHERE user_id = ? AND status = 'issued'
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function getOverdueBooks($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM transactions 
        WHERE user_id = ? AND status = 'issued' AND due_date < CURDATE()
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function getTotalFines($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(fine_amount), 0) FROM transactions 
        WHERE user_id = ? AND fine_paid = FALSE
    ");
    $stmt->execute([$userId]);
    return (float)$stmt->fetchColumn();
}
function getBooksRead($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT book_id) FROM transactions 
        WHERE user_id = ? AND status = 'returned'
    ");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function getTotalReadingHours($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, session_start, session_end) / 60.0), 0)
        FROM reading_sessions 
        WHERE user_id = ? AND session_end IS NOT NULL
    ");
    $stmt->execute([$userId]);
    return round((float)$stmt->fetchColumn(), 1);
}
function getReviewsWritten($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function getWishlistCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
function getCurrentBorrowedBooksDetailed($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT 
            t.*,
            b.title,
            b.author,
            b.pdf_file,
            b.total_pages,
            c.name as category_name,
            DATEDIFF(t.due_date, CURDATE()) as days_remaining,
            CASE 
                WHEN t.due_date < CURDATE() THEN 'overdue'
                WHEN DATEDIFF(t.due_date, CURDATE()) <= 3 THEN 'due_soon'
                ELSE 'normal'
            END as status_type,
            rs.current_page,
            ROUND((rs.current_page / NULLIF(b.total_pages, 0)) * 100, 1) as reading_progress
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        LEFT JOIN categories c ON b.category_id = c.id
        LEFT JOIN (
            SELECT book_id, user_id, MAX(current_page) as current_page
            FROM reading_sessions
            WHERE user_id = ?
            GROUP BY book_id, user_id
        ) rs ON t.book_id = rs.book_id AND t.user_id = rs.user_id
        WHERE t.user_id = ? AND t.status = 'issued'
        ORDER BY t.due_date ASC
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll();
}
function getRecentReadingActivity($pdo, $userId) {
    $stmt = $pdo->prepare("
        SELECT 
            'reading' as type,
            b.title as book_title,
            b.author,
            rs.session_start as activity_date,
            rs.pages_read,
            TIMESTAMPDIFF(MINUTE, rs.session_start, rs.session_end) as minutes_read
        FROM reading_sessions rs
        JOIN books b ON rs.book_id = b.id
        WHERE rs.user_id = ? AND rs.session_end IS NOT NULL
        
        UNION ALL
        
        SELECT 
            'transaction' as type,
            b.title as book_title,
            b.author,
            t.created_at as activity_date,
            NULL as pages_read,
            NULL as minutes_read
        FROM transactions t
        JOIN books b ON t.book_id = b.id
        WHERE t.user_id = ?
        
        ORDER BY activity_date DESC
        LIMIT 10
    ");
    $stmt->execute([$userId, $userId]);
    return $stmt->fetchAll();
}
/**
 * Add Enhanced Book Review
 */
function addBookReview($pdo, $data) {
    validateInput($data, ['user_id', 'book_id', 'rating']);
    
    $userId = (int)$data['user_id'];
    $bookId = (int)$data['book_id'];
    $rating = (int)$data['rating'];
    $reviewText = sanitizeInput($data['review_text'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        sendError('Rating must be between 1 and 5');
    }
    
    try {
        // Check if user has borrowed this book
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM transactions 
            WHERE user_id = ? AND book_id = ?
        ");
        $stmt->execute([$userId, $bookId]);
        
        if ($stmt->fetchColumn() == 0) {
            sendError('You can only review books you have borrowed');
        }
        
        // Check for existing review
        $stmt = $pdo->prepare("
            SELECT id FROM reviews 
            WHERE user_id = ? AND book_id = ?
        ");
        $stmt->execute([$userId, $bookId]);
        
        if ($stmt->fetch()) {
            sendError('You have already reviewed this book');
        }
        
        // Add review
        $stmt = $pdo->prepare("
            INSERT INTO reviews (user_id, book_id, rating, review_text, status, created_at)
            VALUES (?, ?, ?, ?, 'approved', NOW())
        ");
        
        $result = $stmt->execute([$userId, $bookId, $rating, $reviewText]);
        
        if ($result) {
            $reviewId = $pdo->lastInsertId();
            
            // Create notification for user
            createNotification($pdo, $userId, 'Review Added', 'Your review has been successfully added!', 'success');
            
            // Log activity
            logActivity($pdo, $userId, 'review_added', "Added review for book ID: $bookId");
            
            sendResponse([
                'message' => 'Review added successfully',
                'review_id' => $reviewId
            ], 201);
        } else {
            sendError('Failed to add review');
        }
        
    } catch (PDOException $e) {
        error_log("Add review error: " . $e->getMessage());
        sendError('Failed to add review');
    }
}
/**
 * Start Reading Session
 */
function startReadingSession($pdo, $data) {
    validateInput($data, ['user_id', 'book_id']);
    
    $userId = (int)$data['user_id'];
    $bookId = (int)$data['book_id'];
    $deviceType = sanitizeInput($data['device_type'] ?? 'unknown');
    $currentPage = (int)($data['current_page'] ?? 1);
    
    try {
        // End any active sessions for this user/book
        $stmt = $pdo->prepare("
            UPDATE reading_sessions 
            SET session_end = NOW() 
            WHERE user_id = ? AND book_id = ? AND session_end IS NULL
        ");
        $stmt->execute([$userId, $bookId]);
        
        // Start new session
        $stmt = $pdo->prepare("
            INSERT INTO reading_sessions (user_id, book_id, session_start, current_page, device_type)
            VALUES (?, ?, NOW(), ?, ?)
        ");
        $stmt->execute([$userId, $bookId, $currentPage, $deviceType]);
        
        $sessionId = $pdo->lastInsertId();
        
        sendResponse([
            'message' => 'Reading session started',
            'session_id' => $sessionId
        ]);
        
    } catch (PDOException $e) {
        error_log("Start reading session error: " . $e->getMessage());
        sendError('Failed to start reading session');
    }
}
/**
 * End Reading Session
 */
function endReadingSession($pdo, $data) {
    validateInput($data, ['user_id', 'book_id']);
    
    $userId = (int)$data['user_id'];
    $bookId = (int)$data['book_id'];
    $finalPage = (int)($data['final_page'] ?? 1);
    
    try {
        // Update the active session
        $stmt = $pdo->prepare("
            UPDATE reading_sessions 
            SET session_end = NOW(), 
                current_page = ?,
                pages_read = GREATEST(? - current_page, 0)
            WHERE user_id = ? AND book_id = ? AND session_end IS NULL
        ");
        $stmt->execute([$finalPage, $finalPage, $userId, $bookId]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(['message' => 'Reading session ended successfully']);
        } else {
            sendError('No active reading session found');
        }
        
    } catch (PDOException $e) {
        error_log("End reading session error: " . $e->getMessage());
        sendError('Failed to end reading session');
    }
}
/**
 * Get Book Recommendations
 */
function getBookRecommendations($pdo, $userId, $limit = 6) {
    try {
        // Get user's favorite genres based on their reading history
        $stmt = $pdo->prepare("
            SELECT c.id, c.name, COUNT(*) as read_count
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            JOIN categories c ON b.category_id = c.id
            WHERE t.user_id = ? AND t.status = 'returned'
            GROUP BY c.id, c.name
            ORDER BY read_count DESC
            LIMIT 3
        ");
        $stmt->execute([$userId]);
        $favoriteGenres = $stmt->fetchAll();
        
        if (empty($favoriteGenres)) {
            // If no reading history, recommend popular books
            $stmt = $pdo->prepare("
                SELECT DISTINCT b.*, c.name as category_name,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(t.id) as borrow_count
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN reviews r ON b.id = r.book_id
                LEFT JOIN transactions t ON b.id = t.book_id
                WHERE b.status = 'active' AND b.available_quantity > 0
                GROUP BY b.id
                ORDER BY borrow_count DESC, avg_rating DESC
                LIMIT ?
            ");
            $stmt->execute([$limit]);
        } else {
            // Recommend books from favorite genres
            $genreIds = array_column($favoriteGenres, 'id');
            $placeholders = implode(',', array_fill(0, count($genreIds), '?'));
            
            $stmt = $pdo->prepare("
                SELECT DISTINCT b.*, c.name as category_name,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(t.id) as borrow_count
                FROM books b
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN reviews r ON b.id = r.book_id
                LEFT JOIN transactions t ON b.id = t.book_id
                WHERE b.status = 'active' 
                  AND b.available_quantity > 0 
                  AND b.category_id IN ($placeholders)
                  AND b.id NOT IN (
                      SELECT book_id FROM transactions WHERE user_id = ?
                  )
                GROUP BY b.id
                ORDER BY avg_rating DESC, borrow_count DESC
                LIMIT ?
            ");
            $params = array_merge($genreIds, [$userId, $limit]);
            $stmt->execute($params);
        }
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get recommendations error: " . $e->getMessage());
        return [];
    }
}
/**
 * Helper function to create notifications
 */
function createNotification($pdo, $userId, $title, $message, $type = 'info', $actionUrl = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, action_url)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $title, $message, $type, $actionUrl]);
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Create notification error: " . $e->getMessage());
        return false;
    }
}
/**
 * Add to Wishlist
 */
function addToWishlist($pdo, $data) {
    validateInput($data, ['user_id', 'book_id']);
    
    $userId = (int)$data['user_id'];
    $bookId = (int)$data['book_id'];
    
    try {
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO wishlist (user_id, book_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$userId, $bookId]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(['message' => 'Book added to wishlist']);
        } else {
            sendError('Book is already in your wishlist');
        }
        
    } catch (PDOException $e) {
        error_log("Add to wishlist error: " . $e->getMessage());
        sendError('Failed to add to wishlist');
    }
}

/**
 * Get User Wishlist
 */
function getUserWishlist($pdo, $userId) {
    if (!$userId) {
        sendError('User ID required');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT w.*, b.title, b.author, b.pdf_file, c.name as category_name,
                   b.available_quantity,
                   COALESCE(AVG(r.rating), 0) as avg_rating,
                   COUNT(r.id) as review_count
            FROM wishlist w
            JOIN books b ON w.book_id = b.id
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.book_id
            WHERE w.user_id = ? AND b.status = 'active'
            GROUP BY w.id
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$userId]);
        $wishlist = $stmt->fetchAll();
        
        sendResponse($wishlist);
        
    } catch (PDOException $e) {
        error_log("Get wishlist error: " . $e->getMessage());
        sendError('Failed to get wishlist');
    }
}

/**
 * Get User Notifications
 */
function getUserNotifications($pdo, $userId) {
    if (!$userId) {
        sendError('User ID required');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
        
        sendResponse($notifications);
        
    } catch (PDOException $e) {
        error_log("Get notifications error: " . $e->getMessage());
        sendError('Failed to get notifications');
    }
}

/**
 * Mark Notification as Read
 */
function markNotificationRead($pdo, $data) {
    validateInput($data, ['notification_id', 'user_id']);
    
    $notificationId = (int)$data['notification_id'];
    $userId = (int)$data['user_id'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET read_status = TRUE 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$notificationId, $userId]);
        
        sendResponse(['message' => 'Notification marked as read']);
        
    } catch (PDOException $e) {
        error_log("Mark notification read error: " . $e->getMessage());
        sendError('Failed to mark notification as read');
    }
}

/**
 * Vote on Review (Helpful/Not Helpful)
 */
function voteOnReview($pdo, $data) {
    validateInput($data, ['review_id', 'user_id', 'vote_type']);
    
    $reviewId = (int)$data['review_id'];
    $userId = (int)$data['user_id'];
    $voteType = sanitizeInput($data['vote_type']);
    
    if (!in_array($voteType, ['helpful', 'not_helpful'])) {
        sendError('Invalid vote type');
    }
    
    try {
        $pdo->beginTransaction();
        
        // Insert or update vote
        $stmt = $pdo->prepare("
            INSERT INTO review_votes (review_id, user_id, vote_type)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE vote_type = VALUES(vote_type)
        ");
        $stmt->execute([$reviewId, $userId, $voteType]);
        
        // Update helpful count on review
        $stmt = $pdo->prepare("
            UPDATE reviews SET helpful_count = (
                SELECT COUNT(*) FROM review_votes 
                WHERE review_id = ? AND vote_type = 'helpful'
            ) WHERE id = ?
        ");
        $stmt->execute([$reviewId, $reviewId]);
        
        $pdo->commit();
        
        sendResponse(['message' => 'Vote recorded successfully']);
        
    } catch (PDOException $e) {
        $pdo->rollback();
        error_log("Vote on review error: " . $e->getMessage());
        sendError('Failed to record vote');
    }
}

/**
 * Get Reading Statistics
 */
function getReadingStats($pdo, $userId) {
    if (!$userId) {
        sendError('User ID required');
    }
    
    try {
        // Reading stats by month (last 6 months)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(session_start, '%Y-%m') as month,
                COUNT(*) as sessions,
                SUM(pages_read) as total_pages,
                SUM(TIMESTAMPDIFF(MINUTE, session_start, session_end) / 60.0) as hours_read
            FROM reading_sessions
            WHERE user_id = ? AND session_end IS NOT NULL 
              AND session_start >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(session_start, '%Y-%m')
            ORDER BY month
        ");
        $stmt->execute([$userId]);
        $monthlyStats = $stmt->fetchAll();
        
        // Reading by category
        $stmt = $pdo->prepare("
            SELECT 
                c.name as category,
                COUNT(DISTINCT rs.book_id) as books_read,
                SUM(rs.pages_read) as total_pages
            FROM reading_sessions rs
            JOIN books b ON rs.book_id = b.id
            JOIN categories c ON b.category_id = c.id
            WHERE rs.user_id = ? AND rs.session_end IS NOT NULL
            GROUP BY c.id, c.name
            ORDER BY books_read DESC
        ");
        $stmt->execute([$userId]);
        $categoryStats = $stmt->fetchAll();
        
        // Reading streak
        $readingStreak = getReadingStreak($pdo, $userId);
        
        // Current month progress
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(*) as sessions_this_month,
                SUM(pages_read) as pages_this_month,
                SUM(TIMESTAMPDIFF(MINUTE, session_start, session_end) / 60.0) as hours_this_month
            FROM reading_sessions
            WHERE user_id = ? AND session_end IS NOT NULL 
              AND YEAR(session_start) = YEAR(NOW()) 
              AND MONTH(session_start) = MONTH(NOW())
        ");
        $stmt->execute([$userId]);
        $currentMonth = $stmt->fetch();
        
        sendResponse([
            'monthly_stats' => $monthlyStats,
            'category_stats' => $categoryStats,
            'reading_streak' => $readingStreak,
            'current_month' => $currentMonth
        ]);
        
    } catch (PDOException $e) {
        error_log("Get reading stats error: " . $e->getMessage());
        sendError('Failed to get reading statistics');
    }
}
/**
 * Get Reading Streak
 */
function getReadingStreak($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT DISTINCT DATE(session_start) as read_date
            FROM reading_sessions
            WHERE user_id = ? AND session_end IS NOT NULL
            ORDER BY read_date DESC
        ");
        $stmt->execute([$userId]);
        $readingDates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $streak = 0;
        $currentDate = new DateTime();
        
        foreach ($readingDates as $date) {
            $readDate = new DateTime($date);
            $diff = $currentDate->diff($readDate)->days;
            
            if ($diff == $streak) {
                $streak++;
                $currentDate = $readDate;
            } else {
                break;
            }
        }
        
        return $streak;
        
    } catch (Exception $e) {
        error_log("Get reading streak error: " . $e->getMessage());
        return 0;
    }
}
/**
 * Get Favorite Genres
 */
function getFavoriteGenres($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                c.name,
                COUNT(*) as book_count,
                AVG(r.rating) as avg_rating
            FROM transactions t
            JOIN books b ON t.book_id = b.id
            JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.book_id AND r.user_id = t.user_id
            WHERE t.user_id = ?
            GROUP BY c.id, c.name
            ORDER BY book_count DESC
            LIMIT 5
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get favorite genres error: " . $e->getMessage());
        return [];
    }
}
/**
 * Get Unread Notifications
 */
function getUnreadNotifications($pdo, $userId) {
    try {
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE user_id = ? AND read_status = FALSE 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
        
    } catch (PDOException $e) {
        error_log("Get unread notifications error: " . $e->getMessage());
        return [];
    }
}
/**
 * Request Book (Enhanced)
 */
function requestBook($pdo, $data) {
    validateInput($data, ['user_id', 'book_id']);
    
    $userId = (int)$data['user_id'];
    $bookId = (int)$data['book_id'];
    $priority = sanitizeInput($data['priority'] ?? 'normal');
    
    try {
        // Check if book exists and is available
        $stmt = $pdo->prepare("
            SELECT id, title, available_quantity, quantity
            FROM books 
            WHERE id = ? AND status = 'active'
        ");
        $stmt->execute([$bookId]);
        $book = $stmt->fetch();
        
        if (!$book) {
            sendError('Book not found or inactive');
        }
        
        // Check if user already has this book
        $stmt = $pdo->prepare("
            SELECT id FROM transactions 
            WHERE user_id = ? AND book_id = ? AND status = 'issued'
        ");
        $stmt->execute([$userId, $bookId]);
        
        if ($stmt->fetch()) {
            sendError('You have already borrowed this book');
        }
        
        // Check if user has pending request
        $stmt = $pdo->prepare("
            SELECT id FROM book_requests 
            WHERE user_id = ? AND book_id = ? AND status = 'pending'
        ");
        $stmt->execute([$userId, $bookId]);
        
        if ($stmt->fetch()) {
            sendError('You already have a pending request for this book');
        }
        
        // Create book request
        $stmt = $pdo->prepare("
            INSERT INTO book_requests (user_id, book_id, request_date, status, priority)
            VALUES (?, ?, CURDATE(), 'pending', ?)
        ");
        
        $result = $stmt->execute([$userId, $bookId, $priority]);
        
        if ($result) {
            $requestId = $pdo->lastInsertId();
            
            // Create notification
            createNotification(
                $pdo, 
                $userId, 
                'Book Request Submitted', 
                "Your request for '{$book['title']}' has been submitted and is pending approval.",
                'info'
            );
            
            logActivity($pdo, $userId, 'book_requested', "Requested book: {$book['title']}");
            
            sendResponse([
                'message' => 'Book request submitted successfully',
                'request_id' => $requestId,
                'estimated_wait_days' => $book['available_quantity'] > 0 ? 1 : 7
            ], 201);
        } else {
            sendError('Failed to submit book request');
        }
        
    } catch (PDOException $e) {
        error_log("Request book error: " . $e->getMessage());
        sendError('Failed to process book request');
    }
}
/**
 * Remove from Wishlist
 */
function removeFromWishlist($pdo, $userId, $bookId) {
    if (!$userId || !$bookId) {
        sendError('User ID and Book ID required');
    }
    
    try {
        $stmt = $pdo->prepare("
            DELETE FROM wishlist 
            WHERE user_id = ? AND book_id = ?
        ");
        $stmt->execute([$userId, $bookId]);
        
        if ($stmt->rowCount() > 0) {
            sendResponse(['message' => 'Book removed from wishlist']);
        } else {
            sendError('Book not found in wishlist');
        }
        
    } catch (PDOException $e) {
        error_log("Remove from wishlist error: " . $e->getMessage());
        sendError('Failed to remove from wishlist');
    }
}
/**
 * Update Reading Progress
 */
function updateReadingProgress($pdo, $data) {
    validateInput($data, ['user_id', 'book_id', 'current_page']);
    
    $userId = (int)$data['user_id'];
    $bookId = (int)$data['book_id'];
    $currentPage = (int)$data['current_page'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE reading_sessions 
            SET current_page = ?, pages_read = GREATEST(? - 1, 0)
            WHERE user_id = ? AND book_id = ? AND session_end IS NULL
        ");
        $stmt->execute([$currentPage, $currentPage, $userId, $bookId]);
        
        sendResponse(['message' => 'Reading progress updated']);
        
    } catch (PDOException $e) {
        error_log("Update reading progress error: " . $e->getMessage());
        sendError('Failed to update reading progress');
    }
}
/**
 * Update User Profile (Enhanced)
 */
function updateUserProfile($pdo, $data) {
    validateInput($data, ['user_id']);
    
    $userId = (int)$data['user_id'];
    $name = sanitizeInput($data['name'] ?? '');
    $phone = sanitizeInput($data['phone'] ?? '');
    $course = sanitizeInput($data['course'] ?? '');
    $year = sanitizeInput($data['year'] ?? '');
    
    // Handle profile picture upload if present
    $profilePicture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $profilePicture = handleProfilePictureUpload($_FILES['profile_picture'], $userId);
    }
    
    try {
        $sql = "UPDATE users SET name = ?, phone = ?, course = ?, year = ?, updated_at = NOW()";
        $params = [$name, $phone, $course, $year];
        
        if ($profilePicture) {
            $sql .= ", profile_picture = ?";
            $params[] = $profilePicture;
        }
        
        $sql .= " WHERE id = ?";
        $params[] = $userId;
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($stmt->rowCount() > 0) {
            createNotification($pdo, $userId, 'Profile Updated', 'Your profile information has been updated successfully.', 'success');
            logActivity($pdo, $userId, 'profile_updated', 'Profile information updated');
            sendResponse(['message' => 'Profile updated successfully']);
        } else {
            sendError('No changes made or user not found');
        }
        
    } catch (PDOException $e) {
        error_log("Update profile error: " . $e->getMessage());
        sendError('Failed to update profile');
    }
}
/**
 * Handle profile picture upload
 */
function handleProfilePictureUpload($file, $userId) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPEG, PNG, and GIF are allowed.');
    }
    
    if ($file['size'] > $maxSize) {
        throw new Exception('File too large. Maximum size is 5MB.');
    }
    
    $uploadDir = __DIR__ . '/uploads/profiles/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $userId . '_' . time() . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return 'uploads/profiles/' . $filename;
    } else {
        throw new Exception('Failed to upload file.');
    }
}
?>