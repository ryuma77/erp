<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Detailed Debug Product Creation</h2>";

try {
    $pdo = Database::getInstance();
    echo "✅ Database connected successfully!<br>";
    
    // Test 1: Check session
    echo "<h3>1. Session Check</h3>";
    echo "Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
    echo "Session started: " . (session_status() === PHP_SESSION_ACTIVE ? 'YES' : 'NO') . "<br>";
    
    // Test 2: Check table structure
    echo "<h3>2. Table Structure Check</h3>";
    $tables = ['products', 'product_categories', 'audit_trail', 'users'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM public.$table");
        $count = $stmt->fetchColumn();
        echo "Table $table: " . $count . " records<br>";
    }
    
    // Test 3: Manual insertion without using Product class
    echo "<h3>3. Manual Insertion Test</h3>";
    $testData = [
        'product_code' => 'MANUAL-TEST-' . date('His'),
        'product_name' => 'Manual Test Product',
        'description' => 'Test description',
        'category_id' => 1,
        'unit_price' => 100000,
        'cost_price' => 80000,
        'stock_quantity' => 10,
        'min_stock_level' => 5,
        'max_stock_level' => 100,
        'barcode' => '123456789',
        'sku' => 'MANUAL-SKU',
        'weight' => 1.5,
        'dimensions' => '10x5x3',
        'supplier_id' => null,
        'created_by' => $_SESSION['user_id'] ?? 1,
        'is_active' => true
    ];
    
    echo "Test data: <pre>" . print_r($testData, true) . "</pre>";
    
    $sql = "INSERT INTO public.products 
            (product_code, product_name, description, category_id, unit_price, cost_price, 
             stock_quantity, min_stock_level, max_stock_level, barcode, sku, weight, 
             dimensions, supplier_id, created_by, is_active) 
            VALUES (:product_code, :product_name, :description, :category_id, :unit_price, 
                    :cost_price, :stock_quantity, :min_stock_level, :max_stock_level, 
                    :barcode, :sku, :weight, :dimensions, :supplier_id, :created_by, :is_active) 
            RETURNING id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($testData);
    
    if ($result) {
        $productId = $stmt->fetchColumn();
        echo "✅ Manual insertion SUCCESS! Product ID: " . $productId . "<br>";
        
        // Clean up
        $pdo->exec("DELETE FROM public.products WHERE id = " . $productId);
        echo "✅ Manual test data cleaned up<br>";
    } else {
        echo "❌ Manual insertion FAILED!<br>";
        $errorInfo = $stmt->errorInfo();
        echo "SQL Error: " . $errorInfo[2] . "<br>";
    }
    
    // Test 4: Test Product::create with minimal data
    echo "<h3>4. Product::create() Test</h3>";
    $minimalData = [
        'product_name' => 'Minimal Test Product ' . date('His'),
        'unit_price' => 50000,
        'cost_price' => 40000,
        'stock_quantity' => 5
    ];
    
    echo "Minimal data: <pre>" . print_r($minimalData, true) . "</pre>";
    
    $result = Product::create($minimalData);
    
    if ($result) {
        echo "✅ Product::create() SUCCESS! Product ID: " . $result . "<br>";
        
        // Clean up
        $pdo->exec("DELETE FROM public.products WHERE id = " . $result);
        echo "✅ Product::create test data cleaned up<br>";
    } else {
        echo "❌ Product::create() FAILED!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    echo "Trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

// Enable error reporting untuk melihat error langsung
echo "<h3>5. PHP Error Reporting</h3>";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test langsung dengan error display
echo "Error reporting: " . error_reporting() . "<br>";
echo "Display errors: " . ini_get('display_errors') . "<br>";
?>