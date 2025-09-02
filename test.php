<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=digishelf_db", "root", "");
    echo "<h2>✅ SUCCESS! Database Connected!</h2>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM books");
    $result = $stmt->fetch();
    echo "<p>📚 Found {$result['count']} books in database</p>";
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    echo "<p>📁 Found {$result['count']} categories</p>";
    
    echo "<p><strong>🎉 Your database is ready!</strong></p>";
} catch (Exception $e) {
    echo "<h2>❌ ERROR: " . $e->getMessage() . "</h2>";
}
?>