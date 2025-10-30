<?php
// api/test_vendors.php - Simple test
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/database.php';
    
    echo json_encode([
        'success' => true,
        'message' => 'API is working',
        'database' => 'Connected'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>