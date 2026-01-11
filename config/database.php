<?php
class Database {
    private $host = "localhost";
    private $db = "hostel_mess";
    private $user = "root";
    private $pass = "Towfiq7020@";
    
   public function connect() {
        try {
            return new PDO(
                "mysql:host={$this->host};dbname={$this->db}",
                $this->user,
                $this->pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
}
</php>
