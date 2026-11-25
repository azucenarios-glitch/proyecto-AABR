<?php
$servidor = "localhost";
$usuario  = "root";
$clave    = "";
$base     = "Escuela";

$dsn = "mysql:host={$servidor};dbname={$base};charset=utf8mb4";

$opciones = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conexion = new PDO($dsn,$usuario, $clave,$opciones);
} catch (PDOException $e) {
    die("Error de conexion a la base de datos: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, "UTF-8"));
}