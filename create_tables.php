<?php
require_once __DIR__ . '/config/config.php'; // PASTIKAN INI DIPANGGIL
require_once __DIR__ . '/config/database.php';

echo "<h2>Create PostgreSQL Tables</h2>";

try {
    $pdo = Database::getInstance();
    echo "✅ PostgreSQL connected!<br>";

    // Jangan drop table, langsung create jika tidak ada
    $checkTable = "SELECT EXISTS (
        SELECT FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'users'
    )";

    $stmt = $pdo->query($checkTable);
    $tableExists = $stmt->fetchColumn();

    if ($tableExists) {
        echo "ℹ️ Table 'users' already exists<br>";
    } else {
        // Create users table dengan approach yang lebih aman
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
        echo "✅ Table 'users' created successfully!<br>";
    }

    // Check if user already exists
    $checkUser = "SELECT COUNT(*) FROM public.users WHERE email = 'admin@erp.com'";
    $userExists = $pdo->query($checkUser)->fetchColumn();

    if ($userExists > 0) {
        echo "ℹ️ User admin@erp.com already exists<br>";

        // Update password existing user
        $plainPassword = 'password';
        $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

        $updateSql = "UPDATE public.users SET password = :password WHERE email = :email";
        $stmt = $pdo->prepare($updateSql);
        $stmt->execute(['password' => $hashedPassword, 'email' => 'admin@erp.com']);

        echo "✅ Password updated for existing user!<br>";
    } else {
        // Insert new user
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
    }

    echo "Email: <strong>admin@erp.com</strong><br>";
    echo "Password: <strong>password</strong><br>";

    // Verify the user
    $verifySql = "SELECT id, name, email, password FROM public.users WHERE email = 'admin@erp.com'";
    $user = $pdo->query($verifySql)->fetch();

    if ($user) {
        echo "✅ User verified:<br>";
        echo "ID: " . $user['id'] . "<br>";
        echo "Name: " . $user['name'] . "<br>";
        echo "Email: " . $user['email'] . "<br>";

        // Test password verification
        $isValid = password_verify('password', $user['password']);
        echo "Password test: " . ($isValid ? '✅ VALID' : '❌ INVALID') . "<br>";
    }

    echo "<br><h3>✅ Setup Complete!</h3>";
    echo "<a href='login.php' class='btn btn-success'>Login Now</a>";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";

    // Debug info yang benar
    echo "<br><strong>Debug Info:</strong><br>";
    if (defined('DB_NAME')) {
        echo "Database: " . DB_NAME . "<br>";
        echo "Host: " . DB_HOST . "<br>";
        echo "User: " . DB_USER . "<br>";
    } else {
        echo "Config constants not loaded<br>";
    }
}
