<?php
// Test file to debug city fetching
require_once "../classes/tourist.php";

echo "<h2>Test City Fetching</h2>";

// Test with a known province_ID
$test_province_ID = 1; // Ilocos Norte

$t = new Tourist();

echo "<h3>Testing fetchCity($test_province_ID):</h3>";

$cities = $t->fetchCity($test_province_ID);

if ($cities && is_array($cities) && count($cities) > 0) {
    echo "<p style='color: green;'>✓ Found " . count($cities) . " cities</p>";
    echo "<ul>";
    foreach ($cities as $city) {
        echo "<li>ID: " . $city['city_ID'] . " - " . $city['city_name'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>✗ No cities found for province_ID = $test_province_ID</p>";
}

// Test the actual fetch-city.php endpoint
echo "<h3>Testing fetch-city.php endpoint:</h3>";
echo "<p>URL: <a href='fetch-city.php?province_ID=$test_province_ID' target='_blank'>fetch-city.php?province_ID=$test_province_ID</a></p>";

// Show what the endpoint returns
$result = file_get_contents("http://localhost/tour-guide-zamboanga/registration/fetch-city.php?province_ID=$test_province_ID");
echo "<p>Response:</p>";
echo "<pre>" . htmlspecialchars($result) . "</pre>";

// Test with Davao Oriental (province_ID from your screenshot)
echo "<h3>Testing with different provinces:</h3>";
$test_provinces = [1, 2, 3, 50]; // Try a few different province IDs

foreach ($test_provinces as $pid) {
    $cities = $t->fetchCity($pid);
    $count = is_array($cities) ? count($cities) : 0;
    echo "<p>Province ID $pid: <strong>$count cities</strong></p>";
}

?>
