<?php
/*
 * IMAGE UPLOAD HANDLER
 * Purpose: Handle book cover image uploads
 * Save this as: C:\xampp\htdocs\digishelf\backend\upload_image.php
 * 
 * This file handles:
 * - Image upload validation
 * - File storage
 * - Security checks
 */

require_once 'config.php';

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Only POST method allowed', 405);
}

// Check if admin is logged in (you can enhance this with proper admin authentication)
// For now, we'll allow uploads but you should add admin session validation

// Check if file was uploaded
if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] !== UPLOAD_ERR_OK) {
    sendError('No file uploaded or upload error occurred');
}

$file = $_FILES['cover_image'];
$bookId = $_POST['book_id'] ?? null;

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($fileInfo, $file['tmp_name']);
finfo_close($fileInfo);

if (!in_array($mimeType, $allowedTypes)) {
    sendError('Invalid file type. Only JPG, PNG, and WebP images are allowed');
}

// Validate file size (max 5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    sendError('File too large. Maximum size is 5MB');
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'book_' . ($bookId ?? time()) . '_' . uniqid() . '.' . $extension;
$uploadPath = __DIR__ . '/uploads/' . $filename;

// Create uploads directory if it doesn't exist
if (!is_dir(__DIR__ . '/uploads')) {
    mkdir(__DIR__ . '/uploads', 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    sendError('Failed to save uploaded file');
}

// If book ID is provided, update the database
if ($bookId) {
    try {
        $stmt = $pdo->prepare("UPDATE books SET cover_image = ? WHERE id = ?");
        $stmt->execute([$filename, $bookId]);
        
        sendResponse([
            'success' => true,
            'message' => 'Image uploaded and book updated successfully',
            'filename' => $filename,
            'image_path' => '/digishelf/backend/uploads/' . $filename
        ]);
    } catch (PDOException $e) {
        // Delete uploaded file if database update fails
        unlink($uploadPath);
        sendError('Failed to update book with new image');
    }
} else {
    // Just return the upload success
    sendResponse([
        'success' => true,
        'message' => 'Image uploaded successfully',
        'filename' => $filename,
        'image_path' => '/digishelf/backend/uploads/' . $filename
    ]);
}
?>
