<?php

class Database {
    private $host = "localhost";
    private $username = "root";
    private $password = "";
    private $dbname = "tour";
    private $lastError = "";
    protected $conn;
    

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->dbname};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
            return $this->conn;
        } catch (PDOException $e) {
            $this->lastError = "Database connection failed: " . $e->getMessage();
            error_log($this->lastError);
            return false;
        }
    }

    public function setLastError(string $message): void
    {
        $this->lastError = $message;
        error_log("Registration Error: " . $message);
    }

    public function getLastError(): ?string
    {
        return $this->lastError ?: null;
    }

    public function getPDO() { 
        if (!$this->conn) {
            $this->connect();
        }
        return $this->conn;
    }

}