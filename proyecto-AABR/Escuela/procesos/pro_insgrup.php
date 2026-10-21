<?php
$servidor = "localhost"; 
$usuario = "root"; 
$clave = ""; 
$base = "Escuela"; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_in = $_POST['id_in'];
        $id_grupo = $_POST['id_grupo'];
        $id_alumno = $_POST['id_alumno'];
        $status_ins = "ACTA"; // Definido por defecto según esquema
        $sql = "INSERT INTO insgrup (id_in, id_grupo, id_alumno, status_ins) VALUES (:id_in, :id_grupo, :id_alumno, :status_ins)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_in' => $id_in,
            'id_grupo' => $id_grupo,
            'id_alumno' => $id_alumno,
            'status_ins' => $status_ins
        ]);
        echo "<p style='color:green;'>Inscripción a grupo realizada correctamente.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Inscripción A. Grupos</title>
</head>


<body>
<head>
    <figure class="blockquote text-center">
        <blockquote>
            <meta charset='UTF-8'>
            <title>Registro Alumnos</title>
            <style>
        body{font-family:Arial;margin:30px;background: #fff5f7;color: #333}
        form{max-width:500px;background:#fff;padding:25px;border-radius:8px;box-shadow:0 0 10px #f8d7da;border-top:4px solid #bcd30d}
        input,select{width:100%;padding:8px;margin:5px 0 15px;box-sizing:border-box;border:1px solid #f1b0b7;border-radius:4px}
        button{padding:10px 20px;background: #bcd30d;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:bold}
        button:hover{background: #bcd30d}
        a{color: #bcd30d;text-decoration:none;display:inline-block;margin-top:10px;font-weight:bold}
        a:hover{text-decoration: underline;color: #a61e4d}
        p{padding:10px;border-radius:4px;font-weight:bold}
        .ok{background: #d4edda;color: #155724}
        .err{background: #f8d7da;color: #721c24}
    </style>
</head>
<figure class="blockquote text-center">
        <blockquote>
    <h2>Inscripción A. Grupos</h2>
    </blockquote>
</figure>
    <form method="POST">
        <label>ID Inscripción (id-in):</label><br>
        <input type="text" name="id_in" required><br><br>
        <label>ID Grupo:</label><br>
        <input type="text" name="id_grupo" required><br><br>
        <label>ID Alumno:</label><br>
        <input type="text" name="id_alumno" required><br><br>
        <button type="submit">Inscribir</button>
    </blockquote>
</figure>
</form>
</body>
</html>