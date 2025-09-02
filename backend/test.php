<?php
/*
 * BACKEND TESTING FILE
 * Save this as: C:\xampp\htdocs\digishelf\backend\test.php
 * 
 * This file tests if all your backend files are working correctly
 */

echo "<h1>🧪 DigiShelf Backend Test</h1>";
echo "<style>
    body {
        font-family: Arial, sans-serif; 
        padding: 20px; 
        background: #f5f5f5;
        line-height: 1.6;
    } 
    .success { color: green; font-weight: bold; } 
    .error { color: red; font-weight: bold; } 
    .info { color: blue; }
    .warning { color: orange; }
    .test-section { 
        background: white; 
        padding: 15px; 
        margin: 10px 0; 
        border-radius: 8px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .test-item { 
        padding: 5px 0; 
        border-bottom: 1px solid #eee; 
    }
    .test-item:last-child { border-bottom: none; }
</style>";

// Test 1: Check if backend files exist
echo "<div class='test-section'>";
echo "<h2>📁 File Existence Test</h2>";

$backendFiles = [
    'config.php' => 'Database Configuration',
    'auth.php' => 'Authentication API',
    'books.php' => 'Books Management API',
    'user.php' => 'User Management API',
    'admin.php' => 'Admin Management API'
];

$allFilesExist = true;
foreach ($backendFiles as $file => $description) {
    echo "<div class='test-item'>";
    if (file_exists($file)) {
        echo "<span class='success'>✅ $file</span> - $description";
    } else {
        echo "<span class='error'>❌ $file - MISSING!</span> Please create this file.";
        $allFilesExist = false;
    }
    echo "</div>";
}
echo "</div>";

if (!$allFilesExist) {
    echo "<div class='test-section'>";
    echo "<h2 class='error'>⚠️ Setup Required</h2>";
    echo "<p>Some backend files are missing. Please ensure all files are in the correct location:</p>";
    echo "<p><code>C:\\xampp\\htdocs\\digishelf\\backend\\</code></p>";
    echo "</div>";
    exit;
}

// Test 2: Database connection
echo "<div class='test-section'>";
echo "<h2>🔧 Database Connection Test</h2>";

try {
    require_once 'config.php';
    echo "<div class='test-item'><span class='success'>✅ Database connection successful!</span></div>";
    echo "<div class='test-item'><span class='info'>🏠 Host: " . DB_HOST . "</span></div>";
    echo "<div class='test-item'><span class='info'>🗄️ Database: " . DB_NAME . "</span></div>";
    
    // Test 3: Check tables
    echo "</div><div class='test-section'>";
    echo "<h2>📋 Database Tables Test</h2>";
    
    $tables = ['users', 'books', 'categories', 'transactions', 'admins', 'book_requests', 'reviews'];
    $allTablesExist = true;
    
    foreach ($tables as $table) {
        echo "<div class='test-item'>";
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            $count = $stmt->fetchColumn();
            echo "<span class='success'>✅ Table '$table'</span> exists with <strong>$count</strong> records";
        } catch (PDOException $e) {
            echo "<span class='error'>❌ Table '$table'</span> missing or error: " . $e->getMessage();
            $allTablesExist = false;
        }
        echo "</div>";
    }
    
    if (!$allTablesExist) {
        echo "<div class='test-item'>";
        echo "<span class='warning'>⚠️ Import the database schema file (digishelf_db.sql) in phpMyAdmin</span>";
        echo "</div>";
    }
    
    // Test 4: Sample data verification
    echo "</div><div class='test-section'>";
    echo "<h2>📚 Sample Data Test</h2>";
    
    try {
        $stmt = $pdo->query("SELECT title, author FROM books LIMIT 5");
        $sampleBooks = $stmt->fetchAll();
        
        if ($sampleBooks) {
            echo "<div class='test-item'><span class='success'>✅ Sample books found:</span></div>";
            foreach ($sampleBooks as $book) {
                echo "<div class='test-item'>📖 {$book['title']} by {$book['author']}</div>";
            }
        } else {
            echo "<div class='test-item'><span class='warning'>⚠️ No sample books found. Import the database schema.</span></div>";
        }
    } catch (Exception $e) {
        echo "<div class='test-item'><span class='error'>❌ Error reading books: " . $e->getMessage() . "</span></div>";
    }
    
    // Test admin account
    try {
        $stmt = $pdo->query("SELECT username, name FROM admins LIMIT 1");
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo "<div class='test-item'><span class='success'>✅ Admin account found:</span> {$admin['username']} ({$admin['name']})</div>";
        } else {
            echo "<div class='test-item'><span class='warning'>⚠️ No admin account found. Check database import.</span></div>";
        }
    } catch (Exception $e) {
        echo "<div class='test-item'><span class='error'>❌ Error reading admin: " . $e->getMessage() . "</span></div>";
    }
    
    // Test 5: API endpoints
    echo "</div><div class='test-section'>";
    echo "<h2>🌐 API Endpoints Test</h2>";
    
    $apiTests = [
        'books.php?action=categories' => 'Get Categories',
        'books.php?action=stats' => 'Book Statistics',
        'auth.php?action=invalid' => 'Authentication (should return error)'
    ];
    
    foreach ($apiTests as $endpoint => $description) {
        $url = "http://localhost/digishelf/backend/$endpoint";
        echo "<div class='test-item'>";
        echo "<span class='info'>🔗 $description:</span> ";
        echo "<a href='$url' target='_blank'>$url</a>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='test-item'><span class='error'>❌ Backend test failed: " . $e->getMessage() . "</span></div>";
}

echo "</div>";

// Test results summary
echo "<div class='test-section'>";
echo "<h2>🎯 Next Steps</h2>";
echo "<ol>";
echo "<li><strong>If all tests pass</strong>: Your backend is ready! ✅</li>";
echo "<li><strong>Start your React frontend</strong>: <code>npm start</code> in your React app directory</li>";
echo "<li><strong>Test the full application</strong>: Frontend should connect to this backend</li>";
echo "<li><strong>Register a new user</strong> and test the login flow</li>";
echo "<li><strong>Test admin panel</strong> with credentials: admin/password</li>";
echo "</ol>";

echo "<h3>🔑 Default Login Credentials</h3>";
echo "<div style='background: #e8f4f8; padding: 10px; border-radius: 5px;'>";
echo "<p><strong>Admin:</strong> Username = <code>admin</code> | Password = <code>password</code></p>";
echo "<p><strong>Student:</strong> Register a new account or use demo data in registration form</p>";
echo "</div>";

echo "<h3>🌐 Important URLs</h3>";
echo "<ul>";
echo "<li><strong>Backend API Base:</strong> <code>http://localhost/digishelf/backend/</code></li>";
echo "<li><strong>Frontend (React):</strong> <code>http://localhost:3000</code></li>";
echo "<li><strong>phpMyAdmin:</strong> <code>http://localhost/phpmyadmin</code></li>";
echo "</ul>";

echo "</div>";

echo "<hr>";
echo "<div style='text-align:center; margin-top:30px; background: linear-gradient(135deg, #bf6b2c, #d4782e); color: white; padding: 20px; border-radius: 10px;'>";
echo "<h2>🎉 DigiShelf Backend Testing Complete!</h2>";
echo "<p>Your library management system backend is ready to serve students and administrators.</p>";
echo "</div>";
?>