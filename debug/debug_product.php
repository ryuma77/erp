<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Debug Product Creation</h2>";

// Test database connection
try {
    $pdo = Database::getInstance();
    echo "✅ Database connected successfully!<br>";
    
    // Check if tables exist
    $tables = ['products', 'product_categories', 'audit_trail'];
    foreach ($tables as $table) {
        $stmt = $pdo->query("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = '$table')");
        $exists = $stmt->fetchColumn();
        echo "Table $table: " . ($exists ? '✅ EXISTS' : '❌ MISSING') . "<br>";
    }
    
    echo "<br>";
    
    // Test sample data insertion
    $testData = [
        'product_code' => 'TEST-001',
        'product_name' => 'Test Product',
        'description' => 'Test product description',
        'category_id' => 1,
        'unit_price' => 100000,
        'cost_price' => 80000,
        'stock_quantity' => 10,
        'min_stock_level' => 5,
        'max_stock_level' => 100,
        'barcode' => '123456789',
        'sku' => 'TEST-SKU-001',
        'weight' => 1.5,
        'dimensions' => '10x5x3',
        'supplier_id' => null,
        'created_by' => 1
    ];
    
    echo "<h3>Testing Product Creation</h3>";
    
    // Manual insertion test
    $sql = "INSERT INTO public.products 
            (product_code, product_name, description, category_id, unit_price, cost_price, 
             stock_quantity, min_stock_level, max_stock_level, barcode, sku, weight, 
             dimensions, supplier_id, created_by) 
            VALUES (:product_code, :product_name, :description, :category_id, :unit_price, 
                    :cost_price, :stock_quantity, :min_stock_level, :max_stock_level, 
                    :barcode, :sku, :weight, :dimensions, :supplier_id, :created_by) 
            RETURNING id";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($testData);
    $productId = $stmt->fetchColumn();
    
    if ($productId) {
        echo "✅ Manual insertion successful! Product ID: " . $productId . "<br>";
        
        // Test Product class
        $result = Product::create($testData);
        if ($result) {
            echo "✅ Product::create() successful! Product ID: " . $result . "<br>";
        } else {
            echo "❌ Product::create() failed!<br>";
        }
        
        // Clean up test data
        $pdo->exec("DELETE FROM public.products WHERE product_code = 'TEST-001'");
        echo "✅ Test data cleaned up<br>";
        
    } else {
        echo "❌ Manual insertion failed!<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
    
    // Show detailed error info
    echo "<br><strong>Error Details:</strong><br>";
    echo "Error Code: " . $e->getCode() . "<br>";
    echo "File: " . $e->getFile() . "<br>";
    echo "Line: " . $e->getLine() . "<br>";
}
?>