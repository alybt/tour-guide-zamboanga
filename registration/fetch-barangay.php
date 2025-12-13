<?php
require_once "../classes/tourist.php";
$city_ID = $_GET["city_ID"];
$t = new Tourist();

echo "<option value=''>--SELECT BARANGAY--</option>";
foreach ($t->fetchBarangay($city_ID) as $b){
    echo "<option value='".$b["barangay_ID"]."'>".$b["barangay_name"]."</option>";
}
