<?php
// test_api.php - File untuk test API endpoint
echo "<h3>Testing Product Vendors API</h3>";

// Test dengan data dummy
$testData = [
    'action' => 'create_vendor_and_link',
    'product_id' => 1,
    'new_vendor_data' => json_encode([
        'partner_name' => 'Test Vendor API',
        'partner_type' => 'vendor',
        'contact_person' => 'Test Contact',
        'phone' => '123456789',
        'email' => 'test@vendor.com'
    ]),
    'vendor_data' => json_encode([
        'cost_price' => 100000,
        'lead_time_days' => 7,
        'moq' => 10,
        'is_primary' => true
    ])
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/erp/api/product_vendors.php');
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $testData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: " . $httpCode . "<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";

// Test langsung require files
echo "<h4>Testing File Requires:</h4>";
$files = [
    'config/config.php',
    'includes/auth.php',
    'includes/functions.php', 
    'config/database.php',
    'includes/product.php',
    'includes/partner.php'
];

foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✅ $file exists<br>";
    } else {
        echo "❌ $file NOT found<br>";
    }
}
?>