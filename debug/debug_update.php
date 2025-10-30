<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Debug Product Update</h2>";

try {
    $pdo = Database::getInstance();
    echo "✅ Database connected successfully!<br>";
    
    // First, create a test product to update
    echo "<h3>1. Creating test product...</h3>";
    $testData = [
        'product_name' => 'Test Product for Update ' . date('His'),
        'unit_price' => 100000,
        'cost_price' => 80000,
        'stock_quantity' => 10
    ];
    
    $productId = Product::create($testData);
    
    if ($productId) {
        echo "✅ Test product created! ID: " . $productId . "<br>";
        
        // Test 2: Update the product
        echo "<h3>2. Testing update...</h3>";
        $updateData = [
            'product_name' => 'Updated Test Product ' . date('His'),
            'unit_price' => 120000,
            'cost_price' => 90000,
            'stock_quantity' => 15,
            'description' => 'This is an updated description',
            'category_id' => 1,
            'min_stock_level' => 5,
            'is_active' => true
        ];
        
        echo "Update data: <pre>" . print_r($updateData, true) . "</pre>";
        
        $updateResult = Product::update($productId, $updateData);
        
        if ($updateResult) {
            echo "✅ Update SUCCESS!<br>";
            
            // Verify the update
            $updatedProduct = Product::getById($productId);
            if ($updatedProduct) {
                echo "✅ Verification - Updated name: " . $updatedProduct['product_name'] . "<br>";
            }
        } else {
            echo "❌ Update FAILED!<br>";
            
            // Try minimal update data
            echo "<h3>3. Testing minimal update...</h3>";
            $minimalUpdate = [
                'product_name' => 'Minimal Update Test',
                'unit_price' => 110000,
                'cost_price' => 85000,
                'stock_quantity' => 12
            ];
            
            $minimalResult = Product::update($productId, $minimalUpdate);
            echo "Minimal update result: " . ($minimalResult ? "SUCCESS" : "FAILED") . "<br>";
        }
        
        // Clean up
        echo "<h3>4. Cleaning up...</h3>";
        $pdo->exec("DELETE FROM public.products WHERE id = " . $productId);
        $pdo->exec("DELETE FROM public.audit_trail WHERE record_id = " . $productId . " AND table_name = 'products'");
        echo "✅ Test data cleaned up<br>";
        
    } else {
        echo "❌ Failed to create test product!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

// Check PHP error logs
echo "<br><h3>PHP Error Logs</h3>";
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath)) {
    $errors = tailCustom($errorLogPath, 5);
    echo "<pre>Last 5 errors:\n" . htmlspecialchars($errors) . "</pre>";
} else {
    echo "Error log not found or not accessible<br>";
}

function tailCustom($filepath, $lines = 1) {
    try {
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;
        
        $buffer = 4096;
        fseek($f, -1, SEEK_END);
        
        if (fread($f, 1) != "\n") $lines -= 1;
        
        $output = '';
        $chunk = '';
        
        while (ftell($f) > 0 && $lines >= 0) {
            $seek = min(ftell($f), $buffer);
            fseek($f, -$seek, SEEK_CUR);
            $output = ($chunk = fread($f, $seek)) . $output;
            fseek($f, -mb_strlen($chunk, '8bit'), SEEK_CUR);
            $lines -= substr_count($chunk, "\n");
        }
        
        while ($lines++ < 0) {
            $output = substr($output, strpos($output, "\n") + 1);
        }
        
        fclose($f);
        return $output;
    } catch (Exception $e) {
        return "Cannot read error log: " . $e->getMessage();
    }
}
?>