<?php
$host = 'localhost';
$dbname = 'escuela';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_capcal = $_POST['id_capcal'];
        $id_alumno = $_POST['id_alumno'];
        $cal = $_POST['cal'];
        $id_unidad = $_POST['id_unidad'];

        $sql = "INSERT INTO capcal (id_capcal, id_alumno, cal, id_unidad) VALUES (:id_capcal, :id_alumno, :cal, :id_unidad)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_capcal' => $id_capcal,
            'id_alumno' => $id_alumno,
            'cal' => $cal,
            'id_unidad' => $id_unidad
        ]);

        echo "<p style='color:green;'>Calificación capturada exitosamente.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Captura de Calificaciones</title>
</head>
<body>

<head>
    <figure class="blockquote text-center">
        <blockquote>
    <style>
        body{font-family:Arial;margin:30px;background: #fff5f7;color: #333}
        form{max-width:500px;background:#fff;padding:25px;border-radius:8px;box-shadow:0 0 10px #f8d7da;border-top:4px solid #d3940d}
        input,select{width:100%;padding:8px;margin:5px 0 15px;box-sizing:border-box;border:1px solid #f1b0b7;border-radius:4px}
        button{padding:10px 20px;background: #d3940d;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:bold}
        button:hover{background: #d3940d}
        a{color: #bcd30d;text-decoration:none;display:inline-block;margin-top:10px;font-weight:bold}
        a:hover{text-decoration: underline;color: #a61e4d}
        p{padding:10px;border-radius:4px;font-weight:bold}
        .ok{background: #d4edda;color: #155724}
        .err{background: #f8d7da;color: #721c24}
    </style>
</head> 
<figure class="blockquote text-center">
    <blockquote>
    <h2>Captura Calificaciones</h2>
    </blockquote>
</figure>    
    <form method="POST">
        <label>ID Captura (id-capcal):</label><br>
        <input type="text" name="id_capcal" required><br><br>

        <label>ID Alumno:</label><br>
        <input type="text" name="id_alumno" required><br><br>

        <label>Calificación (cal):</label><br>
        <input type="number" step="0.01" name="cal" required><br><br>

        <label>ID Unidad:</label><br>
        <input type="text" name="id_unidad" required><br><br>
        
        <button type="submit">Guardar Calificación</button>
    </blockquote>
</figure>
</form>
</body>
</html>