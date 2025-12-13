<?php
require_once "../classes/tourist.php";
$province_ID = $_GET["province_ID"];
$t = new Tourist();

echo "<option value=''>--SELECT CITY--</option>";
foreach ($t->fetchCity($province_ID) as $c){
    echo "<option value='".$c["city_ID"]."'>".$c["city_name"]."</option>";
}
