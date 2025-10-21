<?php

// Definición de constantes solo si no están definidas
if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
if (!defined('DB_NAME')) define('DB_NAME', 'negocio_fotografia');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Definir la clase solo si no existe
if (!class_exists('DatabaseConnection')) {
    class DatabaseConnection {
        private static $instance = null;
        private $pdo;
        private function __construct() {
            try {
                $this->pdo = new PDO(
                    "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=utf8mb4",
                    DB_USER,
                    DB_PASS,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                throw new Exception("Error al conectar con la base de datos: " . $e->getMessage());
            }
        }
        public static function getInstance() {
            if (self::$instance === null) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        public function getConnection() {
            return $this->pdo;
        }
        public function __clone() {}
        public function __wakeup() {}
    }
}

// Crear instancia global de $pdo para compatibilidad
if (!isset($pdo)) {
    $pdo = DatabaseConnection::getInstance()->getConnection();
}
