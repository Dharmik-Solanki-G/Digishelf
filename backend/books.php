<?php
/*
 * BOOKS MANAGEMENT API
 * Purpose: Book management and search
 * Save this as: C:\xampp\htdocs\digishelf\backend\books.php
 * 
 * This file handles:
 * - Get all books
 * - Search books
 * - Add/Edit/Delete books (admin only)
 * - Get book categories
 * - Book statistics
 */

require_once 'config.php';

// Get request method and input data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Route requests based on method
switch ($method) {
    case 'GET':
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'all':
                getAllBooks($pdo);
                break;
            case 'search':
                searchBooks($pdo);
                break;
            case 'categories':
                getCategories($pdo);
                break;
            case 'stats':
                getBookStats($pdo);
                break;
            case 'single':
                getBookById($pdo, $_GET['id'] ?? null);
                break;
            default:
                getAllBooks($pdo);  // Default action
        }
        break;
        
    case 'POST':
        addBook($pdo, $input);
        break;
        
    case 'PUT':
        updateBook($pdo, $input);
        break;
        
    case 'DELETE':
        deleteBook($pdo, $_GET['id'] ?? null);
        break;
        
    default:
        sendError('Method not allowed', 405);
}

/**
 * Get All Books with Pagination
 */
function getAllBooks($pdo) {
    try {
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        // Get books with category names and average ratings
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                c.name as category_name,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.book_id
            WHERE b.status = 'active'
            GROUP BY b.id
            ORDER BY b.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        $books = $stmt->fetchAll();
        
        // Get total count for pagination
        $countStmt = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'active'");
        $totalBooks = $countStmt->fetchColumn();
        
        sendResponse([
            'books' => $books,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => ceil($totalBooks / $limit),
                'total_books' => (int)$totalBooks,
                'books_per_page' => $limit
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log("Get books error: " . $e->getMessage());
        sendError('Failed to fetch books');
    }
}

/**
 * Search Books
 */
function searchBooks($pdo) {
    try {
        $query = sanitizeInput($_GET['q'] ?? '');
        $category = (int)($_GET['category'] ?? 0);
        $page = (int)($_GET['page'] ?? 1);
        $limit = (int)($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        
        if (empty($query) && $category == 0) {
            getAllBooks($pdo);
            return;
        }
        
        // Build search query
        $sql = "
            SELECT 
                b.*,
                c.name as category_name,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.book_id
            WHERE b.status = 'active'
        ";
        
        $params = [];
        
        // Add search conditions
        if (!empty($query)) {
            $sql .= " AND (b.title LIKE ? OR b.author LIKE ? OR b.description LIKE ?)";
            $searchTerm = "%$query%";
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
        }
        
        if ($category > 0) {
            $sql .= " AND b.category_id = ?";
            $params[] = $category;
        }
        
        $sql .= " GROUP BY b.id ORDER BY b.title LIMIT ? OFFSET ?";
        $params = array_merge($params, [$limit, $offset]);
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $books = $stmt->fetchAll();
        
        sendResponse([
            'books' => $books,
            'search_query' => $query,
            'category_filter' => $category,
            'total_found' => count($books)
        ]);
        
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        sendError('Search failed. Please try again.');
    }
}

/**
 * Get Single Book by ID
 */
function getBookById($pdo, $id) {
    if (!$id) {
        sendError('Book ID required');
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT 
                b.*,
                c.name as category_name,
                COALESCE(AVG(r.rating), 0) as avg_rating,
                COUNT(r.id) as review_count
            FROM books b
            LEFT JOIN categories c ON b.category_id = c.id
            LEFT JOIN reviews r ON b.id = r.book_id
            WHERE b.id = ? AND b.status = 'active'
            GROUP BY b.id
        ");
        $stmt->execute([$id]);
        $book = $stmt->fetch();
        
        if (!$book) {
            sendError('Book not found', 404);
        }
        
        // Get reviews for this book
        $reviewStmt = $pdo->prepare("
            SELECT 
                r.*,
                u.name as user_name,
                u.student_id
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.book_id = ?
            ORDER BY r.created_at DESC
            LIMIT 10
        ");
        $reviewStmt->execute([$id]);
        $reviews = $reviewStmt->fetchAll();
        
        $book['reviews'] = $reviews;
        
        sendResponse($book);
        
    } catch (PDOException $e) {
        error_log("Get book error: " . $e->getMessage());
        sendError('Failed to fetch book details');
    }
}

/**
 * Get All Categories
 */
function getCategories($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                c.*,
                COUNT(b.id) as book_count
            FROM categories c
            LEFT JOIN books b ON c.id = b.category_id AND b.status = 'active'
            GROUP BY c.id
            ORDER BY c.name
        ");
        $categories = $stmt->fetchAll();
        
        sendResponse($categories);
        
    } catch (PDOException $e) {
        error_log("Get categories error: " . $e->getMessage());
        sendError('Failed to fetch categories');
    }
}

/**
 * Add New Book (Admin Only)
 */
function addBook($pdo, $data) {
    // Basic validation
    validateInput($data, ['title', 'author', 'category_id']);
    
    // Sanitize inputs
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
        // Check if ISBN already exists (if provided)
        if (!empty($isbn)) {
            $stmt = $pdo->prepare("SELECT id FROM books WHERE isbn = ?");
            $stmt->execute([$isbn]);
            if ($stmt->fetch()) {
                sendError('Book with this ISBN already exists');
            }
        }
        
        // Insert new book
        $stmt = $pdo->prepare("
            INSERT INTO books (
                title, author, isbn, category_id, description, 
                publisher, publication_year, pages, quantity, 
                available_quantity, status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())
        ");
        
        $result = $stmt->execute([
            $title, $author, $isbn, $categoryId, $description,
            $publisher, $publicationYear, $pages, $quantity, $quantity
        ]);
        
        if ($result) {
            $bookId = $pdo->lastInsertId();
            logActivity($pdo, null, 'book_added', "New book added: $title");
            
            sendResponse([
                'message' => 'Book added successfully',
                'book_id' => $bookId
            ], 201);
        } else {
            sendError('Failed to add book');
        }
        
    } catch (PDOException $e) {
        error_log("Add book error: " . $e->getMessage());
        sendError('Failed to add book. Please check all fields.');
    }
}

/**
 * Update Book (Admin Only)
 */
function updateBook($pdo, $data) {
    validateInput($data, ['id', 'title', 'author']);
    
    $id = (int)$data['id'];
    $title = sanitizeInput($data['title']);
    $author = sanitizeInput($data['author']);
    $isbn = sanitizeInput($data['isbn'] ?? '');
    $categoryId = (int)$data['category_id'];
    $description = sanitizeInput($data['description'] ?? '');
    $publisher = sanitizeInput($data['publisher'] ?? '');
    $publicationYear = (int)($data['publication_year'] ?? null);
    $pages = (int)($data['pages'] ?? null);
    $quantity = (int)($data['quantity'] ?? 1);
    $availableQuantity = (int)($data['available_quantity'] ?? $quantity);
    
    try {
        $stmt = $pdo->prepare("
            UPDATE books SET 
                title = ?, author = ?, isbn = ?, category_id = ?, 
                description = ?, publisher = ?, publication_year = ?, 
                pages = ?, quantity = ?, available_quantity = ?,
                updated_at = NOW()
            WHERE id = ? AND status = 'active'
        ");
        
        $result = $stmt->execute([
            $title, $author, $isbn, $categoryId, $description,
            $publisher, $publicationYear, $pages, $quantity, 
            $availableQuantity, $id
        ]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($pdo, null, 'book_updated', "Book updated: $title (ID: $id)");
            sendResponse(['message' => 'Book updated successfully']);
        } else {
            sendError('Book not found or no changes made', 404);
        }
        
    } catch (PDOException $e) {
        error_log("Update book error: " . $e->getMessage());
        sendError('Failed to update book');
    }
}

/**
 * Delete Book (Admin Only) - Soft Delete
 */
function deleteBook($pdo, $id) {
    if (!$id) {
        sendError('Book ID required');
    }
    
    try {
        // Check if book has active transactions
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM transactions 
            WHERE book_id = ? AND status = 'issued'
        ");
        $stmt->execute([$id]);
        $activeTransactions = $stmt->fetchColumn();
        
        if ($activeTransactions > 0) {
            sendError('Cannot delete book. It has active borrowings.');
        }
        
        // Soft delete (mark as inactive)
        $stmt = $pdo->prepare("
            UPDATE books 
            SET status = 'inactive', updated_at = NOW() 
            WHERE id = ?
        ");
        $result = $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            logActivity($pdo, null, 'book_deleted', "Book deleted (ID: $id)");
            sendResponse(['message' => 'Book deleted successfully']);
        } else {
            sendError('Book not found', 404);
        }
        
    } catch (PDOException $e) {
        error_log("Delete book error: " . $e->getMessage());
        sendError('Failed to delete book');
    }
}

/**
 * Get Book Statistics for Dashboard
 */
function getBookStats($pdo) {
    try {
        // Total active books
        $totalBooks = $pdo->query("SELECT COUNT(*) FROM books WHERE status = 'active'")->fetchColumn();
        
        // Total books issued
        $totalIssued = $pdo->query("SELECT COUNT(*) FROM transactions WHERE status = 'issued'")->fetchColumn();
        
        // Total active users
        $totalUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
        
        // Overdue books
        $overdueBooks = $pdo->query("
            SELECT COUNT(*) FROM transactions 
            WHERE status = 'issued' AND due_date < CURDATE()
        ")->fetchColumn();
        
        // Books by category
        $categoryStats = $pdo->query("
            SELECT 
                c.name,
                COUNT(b.id) as book_count
            FROM categories c
            LEFT JOIN books b ON c.id = b.category_id AND b.status = 'active'
            GROUP BY c.id, c.name
            ORDER BY book_count DESC
        ")->fetchAll();
        
        // Most popular books
        $popularBooks = $pdo->query("
            SELECT 
                b.title,
                b.author,
                COUNT(t.id) as borrow_count
            FROM books b
            LEFT JOIN transactions t ON b.id = t.book_id
            WHERE b.status = 'active'
            GROUP BY b.id
            ORDER BY borrow_count DESC
            LIMIT 5
        ")->fetchAll();
        
        sendResponse([
            'total_books' => (int)$totalBooks,
            'total_issued' => (int)$totalIssued,
            'total_users' => (int)$totalUsers,
            'overdue_books' => (int)$overdueBooks,
            'category_stats' => $categoryStats,
            'popular_books' => $popularBooks
        ]);
        
    } catch (PDOException $e) {
        error_log("Get stats error: " . $e->getMessage());
        sendError('Failed to fetch statistics');
    }
}
?>