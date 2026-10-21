<?php
// Conexión a la base de datos
$servidor = "localhost"; 
$usuario = "root"; 
$clave = ""; 
$base = "Escuela"; 

$conexion = mysqli_connect($servidor, $usuario, $clave, $base);
if (!$conexion) {
    die("❌ Conexión: " . mysqli_connect_error());
}

// Asegurar que la tabla exista
$sql_crear_tabla = "CREATE TABLE IF NOT EXISTS unidad (
    id_unidad VARCHAR(50) NOT NULL PRIMARY KEY,
    descripcion VARCHAR(255) NOT NULL
)";
mysqli_query($conexion, $sql_crear_tabla);

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_unidad = $_POST['id_unidad'];
        $descripcion = $_POST['descripcion'];

        $sql = "INSERT INTO unidad (id_unidad, descripcion) VALUES (?, ?)";
        $stmt = mysqli_prepare($conexion, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $id_unidad, $descripcion);
            mysqli_stmt_execute($stmt);
            
            echo "<p style='color:green;'>Unidad registrada exitosamente.</p>";
            mysqli_stmt_close($stmt);
        } else {
            throw new Exception(mysqli_error($conexion));
        }
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<body>
    <!DOCTYPE html>
    <html lang="es">
        <head>
            <figure class="blockquote text-center">
                <blockquote>
                    <meta charset='UTF-8'>
                    <title>Unidad</title>
                    <style>
                    body { font-family: Arial; margin: 30px; background: #fff5f7; color: #333; }
                    form { max-width: 500px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px #f8d7da; border-top: 4px solid #210747; }
                    input, select { width: 100%; padding: 8px; margin: 5px 0 15px; box-sizing: border-box; border: 1px solid #f1b0b7; border-radius: 4px; }
                    button { padding: 10px 20px; background: #210747; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
                    button:hover { background: #3a1078; }
                    a { color: #bcd30d; text-decoration: none; display: inline-block; margin-top: 10px; font-weight: bold; }
                    a:hover { text-decoration: underline; color: #a61e4d; }
                    p { padding: 10px; border-radius: 4px; font-weight: bold; }
                    </style>
                    </head>
                    <figure class="blockquote text-center">
                        <blockquote>
                            <h2>Unidad</h2>
                        </blockquote>
                    </figure>
                    <form method="POST">
                        <label>ID Unidad:</label><br>
                        <input type="text" name="id_unidad" required><br><br>
                        <label>Descripción:</label><br>
                        <input type="text" name="descripcion" required><br><br>
                        <button type="submit">Guardar Unidad</button> 
                </blockquote>
            </figure>
        </form>
    </body>
</html>