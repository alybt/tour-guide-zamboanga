<?php
require_once "../classes/tourist.php";
$country_ID = $_GET["country_ID"] ?? null;
$t = new Tourist();

echo "<option value=''>--SELECT REGION--</option>";
foreach ($t->fetchRegion($country_ID) as $r){
    echo "<option value='".$r["region_ID"]."'>".$r["region_name"]."</option>";
}
