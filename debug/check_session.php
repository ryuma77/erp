<?php
session_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

echo "<h2>Session & Database Check</h2>";

// Check session
echo "<h3>Session Info:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";
echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
echo "Session user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "<br>";

// Check database
echo "<h3>Database Info:</h3>";
try {
    $pdo = new PDO("pgsql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✅ Database connected successfully!<br>";
    
    // Check users table
    $stmt = $pdo->query("SELECT id, name, email FROM public.users WHERE id = " . ($_SESSION['user_id'] ?? 0));
    $user = $stmt->fetch();
    
    if ($user) {
        echo "✅ User found in database: " . $user['name'] . " (" . $user['email'] . ")<br>";
    } else {
        echo "❌ User not found in database!<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "<br>";
}

// Check if user is logged in via Auth class
echo "<h3>Auth Class Check:</h3>";
echo "Is logged in: " . (Auth::isLoggedIn() ? 'YES' : 'NO') . "<br>";

$currentUser = Auth::getUser();
echo "Current user: <pre>" . print_r($currentUser, true) . "</pre>";
?>