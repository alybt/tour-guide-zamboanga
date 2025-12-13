<?php
require_once "../classes/tourist.php";
$region_ID = $_GET["region_ID"];
$t = new Tourist();

echo "<option value=''>--SELECT PROVINCE--</option>";
foreach ($t->fetchProvince($region_ID) as $p){
    echo "<option value='".$p["province_ID"]."'>".$p["province_name"]."</option>";
}
