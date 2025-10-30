<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

echo "<h2>Debug Login Process</h2>";

// Simulate login process
$email = 'admin@erp.com';
$password = 'password';

echo "Testing login with:<br>";
echo "Email: " . $email . "<br>";
echo "Password: " . $password . "<br><br>";

try {
    // Direct database check
    $pdo = Database::getInstance();
    $sql = "SELECT id, name, email, password, role FROM users WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user) {
        echo "✅ User found in database:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Name: " . $user['name'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";
        echo "Password Hash: " . $user['password'] . "<br>";
        echo "Role: " . $user['role'] . "<br><br>";
        
        // Test password verification
        $isValid = password_verify($password, $user['password']);
        echo "Password verification: " . ($isValid ? '✅ SUCCESS' : '❌ FAILED') . "<br>";
        
        if ($isValid) {
            echo "<br>✅ Login should work!<br>";
            echo "<a href='login.php' class='btn btn-success'>Go to Login Page</a>";
        } else {
            echo "<br>❌ Password verification failed!<br>";
            echo "Possible issues:<br>";
            echo "- Different password in database<br>";
            echo "- Hash corruption<br>";
        }
    } else {
        echo "❌ User not found in database!<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
?>