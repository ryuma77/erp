<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';

echo "<h2>Debug PostgreSQL Schema & Tables</h2>";

try {
    $pdo = Database::getInstance();
    echo "✅ PostgreSQL connected!<br><br>";
    
    // 1. Check current schema
    $stmt = $pdo->query("SELECT current_schema()");
    $currentSchema = $stmt->fetchColumn();
    echo "📋 Current schema: <strong>" . $currentSchema . "</strong><br><br>";
    
    // 2. List all tables in all schemas
    echo "📊 List semua tables di database:<br>";
    $sql = "SELECT schemaname, tablename 
            FROM pg_catalog.pg_tables 
            WHERE schemaname NOT IN ('pg_catalog', 'information_schema')
            ORDER BY schemaname, tablename";
    
    $stmt = $pdo->query($sql);
    $tables = $stmt->fetchAll();
    
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "Schema: <strong>" . $table['schemaname'] . "</strong> | Table: <strong>" . $table['tablename'] . "</strong><br>";
        }
    } else {
        echo "❌ Tidak ada tables ditemukan!<br>";
    }
    
    echo "<br>";
    
    // 3. Check users table specifically
    echo "🔍 Mencari table 'users':<br>";
    $sql = "SELECT schemaname, tablename 
            FROM pg_catalog.pg_tables 
            WHERE tablename = 'users'";
    
    $stmt = $pdo->query($sql);
    $usersTable = $stmt->fetchAll();
    
    if (count($usersTable) > 0) {
        foreach ($usersTable as $table) {
            echo "✅ Table 'users' ditemukan di schema: <strong>" . $table['schemaname'] . "</strong><br>";
        }
    } else {
        echo "❌ Table 'users' tidak ditemukan!<br>";
    }
    
    echo "<br>";
    
    // 4. Try to query users table dengan schema explicit
    echo "🧪 Test query dengan schema explicit:<br>";
    
    // Coba di schema public
    try {
        $sql = "SELECT COUNT(*) as count FROM public.users";
        $count = $pdo->query($sql)->fetchColumn();
        echo "✅ public.users: " . $count . " records<br>";
    } catch (Exception $e) {
        echo "❌ public.users: " . $e->getMessage() . "<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>