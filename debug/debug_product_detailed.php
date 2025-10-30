<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Detailed Debug Product Creation</h2>";

// Test database connection
try {
    $pdo = Database::getInstance();
    echo "✅ Database connected successfully!<br>";
    
    // Test sample data
    $testData = [
        'product_code' => 'TEST-002',
        'product_name' => 'Test Product Detailed',
        'description' => 'Test product description',
        'category_id' => 1,
        'unit_price' => 100000,
        'cost_price' => 80000,
        'stock_quantity' => 10,
        'min_stock_level' => 5,
        'max_stock_level' => 100,
        'barcode' => '123456789',
        'sku' => 'TEST-SKU-002',
        'weight' => 1.5,
        'dimensions' => '10x5x3',
        'supplier_id' => null,
        'is_active' => true
    ];
    
    echo "<h3>Testing Product::create() Step by Step</h3>";
    
    // Test 1: Check session user_id
    echo "1. Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
    
    // Test 2: Check generateProductCode
    echo "2. Generated product code: " . Product::generateProductCode() . "<br>";
    
    // Test 3: Manual step-by-step creation (replicate Product::create)
    echo "3. Manual step-by-step creation:<br>";
    
    $pdo->beginTransaction();
    
    // Generate product code if not provided
    if (empty($testData['product_code'])) {
        $testData['product_code'] = Product::generateProductCode();
    }
    echo "   - Product code: " . $testData['product_code'] . "<br>";
    
    // Set created_by
    $testData['created_by'] = $_SESSION['user_id'] ?? 1;
    echo "   - Created by: " . $testData['created_by'] . "<br>";
    
    // Prepare SQL
    $sql = "INSERT INTO public.products 
            (product_code, product_name, description, category_id, unit_price, cost_price, 
             stock_quantity, min_stock_level, max_stock_level, barcode, sku, weight, 
             dimensions, supplier_id, created_by, is_active) 
            VALUES (:product_code, :product_name, :description, :category_id, :unit_price, 
                    :cost_price, :stock_quantity, :min_stock_level, :max_stock_level, 
                    :barcode, :sku, :weight, :dimensions, :supplier_id, :created_by, :is_active) 
            RETURNING id";
    
    echo "   - SQL prepared<br>";
    
    // Execute
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($testData);
    echo "   - Execute result: " . ($result ? 'SUCCESS' : 'FAILED') . "<br>";
    
    if ($result) {
        $productId = $stmt->fetchColumn();
        echo "   - Product ID returned: " . ($productId ? $productId : 'NULL') . "<br>";
        
        if ($productId) {
            // Test audit trail
            echo "   - Testing audit trail...<br>";
            require_once __DIR__ . '/includes/audit.php';
            $auditResult = AuditTrail::log('products', $productId, 'CREATE', null, $testData);
            echo "   - Audit trail result: " . ($auditResult ? 'SUCCESS' : 'FAILED') . "<br>";
            
            $pdo->commit();
            echo "   - Transaction committed<br>";
            echo "✅ Manual step-by-step SUCCESS! Product ID: " . $productId . "<br>";
            
            // Clean up
            $pdo->exec("DELETE FROM public.products WHERE product_code = 'TEST-002'");
            $pdo->exec("DELETE FROM public.audit_trail WHERE record_id = " . $productId . " AND table_name = 'products'");
            
        } else {
            $pdo->rollBack();
            echo "❌ No product ID returned!<br>";
        }
    } else {
        $pdo->rollBack();
        echo "❌ Execute failed!<br>";
        
        // Show error info
        echo "   - Error info: " . print_r($stmt->errorInfo(), true) . "<br>";
    }
    
    echo "<br>";
    
    // Test 4: Direct Product::create call
    echo "<h3>Testing Product::create() directly</h3>";
    $result = Product::create($testData);
    echo "Product::create() result: " . ($result ? "SUCCESS - ID: " . $result : "FAILED") . "<br>";
    
    if (!$result) {
        echo "❌ Product::create() failed! Check PHP error logs for details.<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
    
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
        echo "Transaction rolled back<br>";
    }
}

// Check PHP error logs location
echo "<br><h3>PHP Error Logs</h3>";
echo "Error log path: " . ini_get('error_log') . "<br>";
echo "Last error: " . error_get_last()['message'] ?? 'No error' . "<br>";
?>