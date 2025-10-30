<?php
// Start session hanya sekali
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/product.php';

echo "<h2>Product Creation Transaction Debug</h2>";

// Enable detailed error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $pdo = Database::getInstance();
    echo "✅ Database connected successfully!<br>";
    
    // Test 1: Simple direct insertion tanpa transaction
    echo "<h3>1. Direct Insertion (No Transaction)</h3>";
    $simpleData = [
        'product_code' => 'DIRECT-TEST-' . date('His'),
        'product_name' => 'Direct Test Product',
        'unit_price' => 100000,
        'cost_price' => 80000,
        'stock_quantity' => 10,
        'created_by' => $_SESSION['user_id']
    ];
    
    $sql = "INSERT INTO public.products 
            (product_code, product_name, unit_price, cost_price, stock_quantity, created_by) 
            VALUES (:product_code, :product_name, :unit_price, :cost_price, :stock_quantity, :created_by) 
            RETURNING id";
    
    $stmt = $pdo->prepare($sql);
    $result = $stmt->execute($simpleData);
    
    if ($result) {
        $productId = $stmt->fetchColumn();
        echo "✅ Direct insertion SUCCESS! Product ID: " . $productId . "<br>";
        
        // Clean up
        $pdo->exec("DELETE FROM public.products WHERE id = " . $productId);
        echo "✅ Direct test cleaned up<br>";
    } else {
        echo "❌ Direct insertion FAILED!<br>";
        $errorInfo = $stmt->errorInfo();
        echo "SQL Error: " . $errorInfo[2] . "<br>";
    }
    
    // Test 2: Test dengan transaction manual
    echo "<h3>2. Manual Transaction Test</h3>";
    try {
        $pdo->beginTransaction();
        
        $manualData = [
            'product_code' => 'MANUAL-TX-' . date('His'),
            'product_name' => 'Manual Transaction Product',
            'unit_price' => 150000,
            'cost_price' => 120000,
            'stock_quantity' => 20,
            'created_by' => $_SESSION['user_id']
        ];
        
        $sql = "INSERT INTO public.products 
                (product_code, product_name, unit_price, cost_price, stock_quantity, created_by) 
                VALUES (:product_code, :product_name, :unit_price, :cost_price, :stock_quantity, :created_by) 
                RETURNING id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($manualData);
        $productId = $stmt->fetchColumn();
        
        if ($productId) {
            echo "✅ Manual transaction SUCCESS! Product ID: " . $productId . "<br>";
            
            // Commit transaction
            $pdo->commit();
            echo "✅ Transaction committed<br>";
            
            // Clean up
            $pdo->exec("DELETE FROM public.products WHERE id = " . $productId);
            echo "✅ Manual transaction cleaned up<br>";
        } else {
            $pdo->rollBack();
            echo "❌ Manual transaction FAILED - no ID returned<br>";
        }
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo "❌ Manual transaction ERROR: " . $e->getMessage() . "<br>";
    }
    
    // Test 3: Test Product::create dengan debug output
    echo "<h3>3. Product::create() with Debug</h3>";
    
    // Temporary override Product::create untuk debug
    class ProductDebug extends Product {
        public static function createDebug($data) {
            echo "=== Product::create Debug Start ===<br>";
            echo "Input data: <pre>" . print_r($data, true) . "</pre>";
            
            $pdo = Database::getInstance();
            try {
                echo "Starting transaction...<br>";
                $pdo->beginTransaction();
                
                // Generate product code
                if (empty($data['product_code'])) {
                    $data['product_code'] = self::generateProductCode();
                    echo "Generated product code: " . $data['product_code'] . "<br>";
                }
                
                // Set created_by
                $data['created_by'] = $_SESSION['user_id'] ?? 1;
                echo "Created by: " . $data['created_by'] . "<br>";
                
                // Set defaults
                $data['is_active'] = $data['is_active'] ?? true;
                $data['min_stock_level'] = $data['min_stock_level'] ?? 0;
                
                echo "Final data: <pre>" . print_r($data, true) . "</pre>";
                
                $sql = "INSERT INTO public.products 
                        (product_code, product_name, unit_price, cost_price, stock_quantity, created_by, is_active, min_stock_level) 
                        VALUES (:product_code, :product_name, :unit_price, :cost_price, :stock_quantity, :created_by, :is_active, :min_stock_level) 
                        RETURNING id";
                
                echo "Executing SQL...<br>";
                $stmt = $pdo->prepare($sql);
                $result = $stmt->execute([
                    'product_code' => $data['product_code'],
                    'product_name' => $data['product_name'],
                    'unit_price' => $data['unit_price'],
                    'cost_price' => $data['cost_price'],
                    'stock_quantity' => $data['stock_quantity'],
                    'created_by' => $data['created_by'],
                    'is_active' => $data['is_active'],
                    'min_stock_level' => $data['min_stock_level']
                ]);
                
                if ($result) {
                    $productId = $stmt->fetchColumn();
                    echo "✅ Insert SUCCESS! Product ID: " . $productId . "<br>";
                    
                    // Commit
                    $pdo->commit();
                    echo "✅ Transaction committed<br>";
                    
                    echo "=== Product::create Debug End ===<br>";
                    return $productId;
                } else {
                    throw new Exception("Execute failed");
                }
                
            } catch (Exception $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                    echo "❌ Transaction rolled back<br>";
                }
                echo "❌ ERROR: " . $e->getMessage() . "<br>";
                echo "=== Product::create Debug End ===<br>";
                return false;
            }
        }
    }
    
    $debugData = [
        'product_name' => 'Debug Test Product ' . date('His'),
        'unit_price' => 200000,
        'cost_price' => 150000,
        'stock_quantity' => 15
    ];
    
    $result = ProductDebug::createDebug($debugData);
    
    if ($result) {
        echo "✅ ProductDebug::createDebug SUCCESS! Product ID: " . $result . "<br>";
        
        // Clean up
        $pdo->exec("DELETE FROM public.products WHERE id = " . $result);
        echo "✅ Debug test cleaned up<br>";
    } else {
        echo "❌ ProductDebug::createDebug FAILED!<br>";
    }
    
} catch (Exception $e) {
    echo "❌ General Exception: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "<br>";
}

// Test 4: Check for any PHP errors
echo "<h3>4. PHP Error Check</h3>";
$lastError = error_get_last();
if ($lastError) {
    echo "Last PHP error: <pre>" . print_r($lastError, true) . "</pre>";
} else {
    echo "No PHP errors detected<br>";
}
?>