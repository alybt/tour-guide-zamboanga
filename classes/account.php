<?php 

require_once __DIR__ . "/../config/database.php";
require_once "trait/account/upload-picture.php"; 

class Account extends Database {

    use AccountProfileTrait;
    

}
?>