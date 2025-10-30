<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Fixed Debug Product Creation</h2>";

// Test database connection
try {
    $pdo = Database::getInstance();
    echo "✅ Database connected successfully!<br>";
    
    // Test sample data
    $testData = [
        'product_code' => 'TEST-003',
        'product_name' => 'Test Product Fixed',
        'description' => 'Test product description',
        'category_id' => 1,
        'unit_price' => 100000,
        'cost_price' => 80000,
        'stock_quantity' => 10,
        'min_stock_level' => 5,
        'max_stock_level' => 100,
        'barcode' => '123456789',
        'sku' => 'TEST-SKU-003',
        'weight' => 1.5,
        'dimensions' => '10x5x3',
        'supplier_id' => null,
        'is_active' => true
    ];
    
    echo "<h3>Testing Product::create() directly</h3>";
    
    // Test 1: Check session user_id
    echo "1. Session user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "<br>";
    
    // Test 2: Direct Product::create call
    echo "2. Calling Product::create()...<br>";
    $result = Product::create($testData);
    
    if ($result) {
        echo "✅ Product::create() SUCCESS! Product ID: " . $result . "<br>";
        
        // Verify the product was created
        $product = Product::getById($result);
        if ($product) {
            echo "✅ Product verified in database:<br>";
            echo "   - Name: " . $product['product_name'] . "<br>";
            echo "   - Code: " . $product['product_code'] . "<br>";
            echo "   - Price: Rp " . number_format($product['unit_price'], 0, ',', '.') . "<br>";
        } else {
            echo "❌ Product not found after creation!<br>";
        }
        
        // Clean up
        $pdo->exec("DELETE FROM public.products WHERE id = " . $result);
        echo "✅ Test data cleaned up<br>";
        
    } else {
        echo "❌ Product::create() FAILED!<br>";
        
        // Try with minimal data
        echo "<h3>Testing with minimal data</h3>";
        $minimalData = [
            'product_name' => 'Minimal Test Product',
            'unit_price' => 1000,
            'cost_price' => 800,
            'stock_quantity' => 5
        ];
        
        $minimalResult = Product::create($minimalData);
        echo "Minimal data result: " . ($minimalResult ? "SUCCESS - ID: " . $minimalResult : "FAILED") . "<br>";
        
        if ($minimalResult) {
            // Clean up
            $pdo->exec("DELETE FROM public.products WHERE id = " . $minimalResult);
        }
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

// Check recent errors from error log
echo "<br><h3>Recent PHP Errors</h3>";
$errorLogPath = ini_get('error_log');
if ($errorLogPath && file_exists($errorLogPath)) {
    $errors = tailCustom($errorLogPath, 10);
    echo "<pre>Last 10 errors:\n" . htmlspecialchars($errors) . "</pre>";
} else {
    echo "Error log not found or not accessible<br>";
}

// Helper function to read last lines of file
function tailCustom($filepath, $lines = 1) {
    try {
        $f = @fopen($filepath, "rb");
        if ($f === false) return false;
        
        $buffer = 4096;
        $fseek = min(0, filesize($filepath));
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