<?php
class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $host = 'localhost';
        $dbname = 'erp';
        $username = 'adempiere';
        $password = 'IDempiere100%'; // GANTI DENGAN PASSWORD POSTGRESQL ANDA

        try {
            $this->pdo = new PDO("pgsql:host=$host;dbname=$dbname", $username, $password);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // Set default schema ke public
            $this->pdo->exec("SET search_path TO public");
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->pdo;
    }

    // Updated query method for PostgreSQL
    public static function query($sql, $params = [])
    {
        $pdo = self::getInstance();
        $stmt = $pdo->prepare($sql);

        // PostgreSQL menggunakan named parameters (:param) atau positional parameters ($1, $2)
        if (strpos($sql, ':') !== false) {
            // Named parameters
            $stmt->execute($params);
        } else {
            // Positional parameters (convert to $1, $2, etc.)
            $stmt->execute(array_values($params));
        }

        return $stmt;
    }
}
