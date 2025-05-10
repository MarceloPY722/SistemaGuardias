<?php
define('DB_HOST', 'localhost'); 
define('DB_USER', 'root');     
define('DB_PASS', '');         
define('DB_NAME', 'Guardias');  

function conectar() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
       
        error_log("Error de conexión: " . $e->getMessage());
        die("Error de conexión a la base de datos. Intente más tarde.");
    }
}
?>