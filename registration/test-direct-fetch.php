<?php
// Direct test of fetch-city.php
echo "<h2>Direct Test: fetch-city.php</h2>";

// Test with Cavite (province_ID = 14 based on your screenshot)
$province_ID = 14;

echo "<h3>Test 1: Direct PHP call</h3>";
require_once "../classes/tourist.php";
$t = new Tourist();
$cities = $t->fetchCity($province_ID);

echo "<p>Province ID: <strong>$province_ID</strong> (Cavite)</p>";
echo "<p>Result count: <strong>" . (is_array($cities) ? count($cities) : 0) . "</strong></p>";

if ($cities && is_array($cities)) {
    echo "<ul>";
    foreach ($cities as $city) {
        echo "<li>ID: " . $city['city_ID'] . " - " . $city['city_name'] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: red;'>No cities returned!</p>";
}

echo "<h3>Test 2: What fetch-city.php returns</h3>";
echo "<p>Simulating: <code>fetch-city.php?province_ID=$province_ID</code></p>";

// Simulate what fetch-city.php does
ob_start();
include 'fetch-city.php';
$output = ob_get_clean();

echo "<p>Output:</p>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

echo "<h3>Test 3: Rendered HTML</h3>";
echo "<select>";
echo $output;
echo "</select>";

echo "<h3>Test 4: JavaScript Fetch Test</h3>";
?>
<script>
console.log("Testing fetch-city.php with province_ID = <?= $province_ID ?>");

fetch("fetch-city.php?province_ID=<?= $province_ID ?>")
    .then(res => {
        console.log("Response status:", res.status);
        return res.text();
    })
    .then(data => {
        console.log("Response data:", data);
        document.getElementById("test-result").innerHTML = data;
        document.getElementById("test-output").textContent = data;
    })
    .catch(err => {
        console.error("Error:", err);
        document.getElementById("test-result").innerHTML = "<option>ERROR: " + err + "</option>";
    });
</script>

<p>JavaScript fetch result:</p>
<select id="test-result">
    <option>Loading...</option>
</select>

<p>Raw output:</p>
<pre id="test-output">Loading...</pre>

<p><a href="tourist-registration.php">Back to Registration</a></p>
