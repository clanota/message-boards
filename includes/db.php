<?php
class DB {
    private static $instance = null;
    
    private function __construct() {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        if ($this->conn->connect_error) {
            error_log("DB Connection failed: " . $this->conn->connect_error);
            throw new Exception("数据库连接失败");
        }
        $this->conn->set_charset("utf8mb4");
    }

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance->conn;
    }
}
