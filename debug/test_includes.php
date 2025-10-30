<?php
// test_includes.php
echo "Testing includes...<br>";

$files = [
    'config/config.php',
    'includes/product.php', 
    'includes/unit.php',
    'includes/auth.php',
    'includes/functions.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
        
        // Check if class exists
        if ($file === 'includes/product.php') {
            require_once $file;
            if (class_exists('Product')) {
                echo "✅ Product class loaded<br>";
                if (method_exists('Product', 'getCategories')) {
                    echo "✅ Product::getCategories() exists<br>";
                } else {
                    echo "❌ Product::getCategories() NOT found<br>";
                }
            } else {
                echo "❌ Product class NOT loaded<br>";
            }
        }
        
    } else {
        echo "❌ $file NOT found<br>";
    }
}
?>