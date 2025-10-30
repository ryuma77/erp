<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>Create/Fix PostgreSQL Tables</h2>";

try {
    $pdo = Database::getInstance();
    echo "✅ PostgreSQL connected!<br>";
    
    // Drop table jika ada di schema public
    try {
        $pdo->exec("DROP TABLE IF EXISTS public.users");
        echo "✅ Old table dropped<br>";
    } catch (Exception $e) {
        echo "ℹ️ No table to drop or error: " . $e->getMessage() . "<br>";
    }
    
    // Create table di schema public
    $sql = "CREATE TABLE public.users (
        id SERIAL PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(20) DEFAULT 'user',
        status VARCHAR(20) DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "✅ Table 'public.users' created successfully!<br>";
    
    // Insert user
    $plainPassword = 'password';
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);
    
    $insertSql = "INSERT INTO public.users (name, email, password, role) 
                  VALUES (:name, :email, :password, :role)";
    $stmt = $pdo->prepare($insertSql);
    $stmt->execute([
        'name' => 'Administrator', 
        'email' => 'admin@erp.com', 
        'password' => $hashedPassword, 
        'role' => 'admin'
    ]);
    
    echo "✅ Default user created!<br>";
    echo "Email: <strong>admin@erp.com</strong><br>";
    echo "Password: <strong>password</strong><br>";
    
    // Verify
    $verifySql = "SELECT * FROM public.users WHERE email = 'admin@erp.com'";
    $user = $pdo->query($verifySql)->fetch();
    
    if ($user) {
        echo "✅ User verified - ID: " . $user['id'] . "<br>";
        
        // Test password
        $isValid = password_verify('password', $user['password']);
        echo "Password test: " . ($isValid ? '✅ VALID' : '❌ INVALID') . "<br>";
    }
    
    echo "<br><h3>✅ Setup Complete!</h3>";
    echo "<a href='login.php' class='btn btn-success'>Login Now</a>";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>