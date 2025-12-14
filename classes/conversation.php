<?php

require_once __DIR__ . "/../config/database.php";
require_once "trait/conversation/to-guide.php";
require_once "trait/conversation/message.php";

class Conversation extends Database {
    use ToGuideTrait, MessageTrait; 

}