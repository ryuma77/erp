<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Test Product Class - Fixed Version</h2>";

try {
    // Test 1: Create with minimal data
    echo "<h3>1. Create with Minimal Data</h3>";
    $minimalData = [
        'product_name' => 'Minimal Test Fixed ' . date('His'),
        'unit_price' => 50000,
        'cost_price' => 40000,
        'stock_quantity' => 5
    ];
    
    $result1 = Product::create($minimalData);
    echo "Result: " . ($result1 ? "SUCCESS - ID: " . $result1 : "FAILED") . "<br>";
    
    // Test 2: Update the product
    if ($result1) {
        echo "<h3>2. Update Product</h3>";
        $updateData = [
            'product_name' => 'Updated Minimal Product',
            'unit_price' => 55000,
            'stock_quantity' => 8
        ];
        
        $result2 = Product::update($result1, $updateData);
        echo "Update result: " . ($result2 ? "SUCCESS" : "FAILED") . "<br>";
        
        // Clean up
        $pdo = Database::getInstance();
        $pdo->exec("DELETE FROM public.products WHERE id = " . $result1);
        echo "Test data cleaned up<br>";
    }
    
    // Test 3: Create with full data
    echo "<h3>3. Create with Full Data</h3>";
    $fullData = [
        'product_name' => 'Full Test Product ' . date('His'),
        'description' => 'Complete description',
        'category_id' => 1,
        'unit_price' => 150000,
        'cost_price' => 120000,
        'stock_quantity' => 20,
        'min_stock_level' => 5,
        'barcode' => '123456789',
        'is_active' => true
    ];
    
    $result3 = Product::create($fullData);
    echo "Full data result: " . ($result3 ? "SUCCESS - ID: " . $result3 : "FAILED") . "<br>";
    
    if ($result3) {
        $pdo->exec("DELETE FROM public.products WHERE id = " . $result3);
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
}
?>