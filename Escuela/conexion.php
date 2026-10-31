<?php
$servidor = "localhost";
$usuario = "root";
$clave = "";
$base = "Escuela";

try {
    $conexion = new PDO("mysql:host=$servidor;dbname=$base;charset=utf8", $usuario, $clave);
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // echo "Conexión exitosa";
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>