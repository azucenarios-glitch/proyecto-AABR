<?php
// Conexión a la base de datos
$host = 'localhost';
$dbname = 'escuela';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_unidad = $_POST['id_unidad'];
        $descripcion = $_POST['descripcion'];

        $sql = "INSERT INTO unidad (id_unidad, descripcion) VALUES (:id_unidad, :descripcion)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['id_unidad' => $id_unidad, 'descripcion' => $descripcion]);

        echo "<p style='color:green;'>Unidad registrada exitosamente.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Unidad</title>
</head>
<body>
    <figure class="blockquote text-center">
        <blockquote>
            <style>
            body{font-family:Arial;margin:30px;background: #fff5f7;color: #333}
            form{max-width:500px;background:#fff;padding:25px;border-radius:8px;box-shadow:0 0 10px #f8d7da;border-top:4px solid #210747}
            input,select{width:100%;padding:8px;margin:5px 0 15px;box-sizing:border-box;border:1px solid #f1b0b7;border-radius:4px}
            button{padding:10px 20px;background: #210747;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:bold}
        button:hover{background: #210747}
        a{color: #bcd30d;text-decoration:none;display:inline-block;margin-top:10px;font-weight:bold}
        a:hover{text-decoration: underline;color: #a61e4d}
        p{padding:10px;border-radius:4px;font-weight:bold}
        .ok{background: #d4edda;color: #155724}
        .err{background: #f8d7da;color: #721c24}
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