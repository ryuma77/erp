<?php
// Start session hanya sekali
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Final Product Creation Debug</h2>";

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = Database::getInstance();
    echo "✅ Database connected successfully!<br>";
    
    // Test 1: Test Product::create langsung dengan data minimal
    echo "<h3>1. Testing Product::create() Directly</h3>";
    
    $testData = [
        'product_name' => 'Final Test Product ' . date('His'),
        'unit_price' => 100000,
        'cost_price' => 80000,
        'stock_quantity' => 10
    ];
    
    echo "Test data: <pre>" . print_r($testData, true) . "</pre>";
    
    // Langsung panggil Product::create
    $result = Product::create($testData);
    
    if ($result) {
        echo "✅ Product::create SUCCESS! Product ID: " . $result . "<br>";
        
        // Verify the product
        $product = Product::getById($result);
        if ($product) {
            echo "✅ Product verified: " . $product['product_name'] . " (" . $product['product_code'] . ")<br>";
        }
        
        // Clean up
        $pdo->exec("DELETE FROM public.products WHERE id = " . $result);
        echo "✅ Test data cleaned up<br>";
    } else {
        echo "❌ Product::create FAILED!<br>";
        
        // Coba dengan data lengkap
        echo "<h3>2. Testing with Full Data</h3>";
        $fullData = [
            'product_code' => 'FULL-TEST-' . date('His'),
            'product_name' => 'Full Test Product ' . date('His'),
            'description' => 'This is a test description',
            'category_id' => 1,
            'unit_price' => 150000,
            'cost_price' => 120000,
            'stock_quantity' => 25,
            'min_stock_level' => 5,
            'max_stock_level' => 100,
            'barcode' => '123456789',
            'sku' => 'TEST-SKU-001',
            'weight' => 1.5,
            'dimensions' => '10x5x3',
            'supplier_id' => null,
            'is_active' => true
        ];
        
        $fullResult = Product::create($fullData);
        echo "Full data result: " . ($fullResult ? "SUCCESS - ID: " . $fullResult : "FAILED") . "<br>";
        
        if ($fullResult) {
            $pdo->exec("DELETE FROM public.products WHERE id = " . $fullResult);
        }
    }
    
    // Test 2: Check what's in the products table
    echo "<h3>3. Checking Products Table</h3>";
    $stmt = $pdo->query("SELECT id, product_code, product_name, unit_price FROM public.products ORDER BY id DESC LIMIT 5");
    $recentProducts = $stmt->fetchAll();
    
    if ($recentProducts) {
        echo "Recent products in database:<br>";
        foreach ($recentProducts as $product) {
            echo "- ID: " . $product['id'] . " | Code: " . $product['product_code'] . " | Name: " . $product['product_name'] . " | Price: " . $product['unit_price'] . "<br>";
        }
    } else {
        echo "No products in database<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Test 3: Check PHP error log directly
echo "<h3>4. PHP Configuration</h3>";
echo "error_log: " . ini_get('error_log') . "<br>";
echo "display_errors: " . ini_get('display_errors') . "<br>";
echo "log_errors: " . ini_get('log_errors') . "<br>";

// Create a test error to see if logging works
error_log("=== TEST ERROR LOG MESSAGE ===");
?>