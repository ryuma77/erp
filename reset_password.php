<?php
require_once __DIR__ . '/config/database.php';

echo "<h2>Reset PostgreSQL Password</h2>";

try {
    $pdo = Database::getInstance();
    
    // Create fresh password hash
    $plainPassword = 'password';
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    echo "Plain password: " . $plainPassword . "<br>";
    echo "Hashed password: " . $hashedPassword . "<br>";
    
    // Delete existing user
    $pdo->exec("DELETE FROM users WHERE email = 'admin@erp.com'");
    
    // Insert new user
    $sql = "INSERT INTO users (name, email, password, role) VALUES ($1, $2, $3, $4)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['Administrator', 'admin@erp.com', $hashedPassword, 'admin']);
    
    echo "✅ User created successfully!<br>";
    echo "Email: <strong>admin@erp.com</strong><br>";
    echo "Password: <strong>password</strong><br>";
    echo "<br><a href='login.php' class='btn btn-success'>Login Now</a>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>