<?php
$host = 'localhost';
$dbname = 'escuela';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id_asignat = $_POST['id_asignat'];
        $id_mat = $_POST['id_mat'];
        $id_alumno = $_POST['id_alumno'];
        $id_profesor = $_POST['id_profesor'];

        $sql = "INSERT INTO asignat (id_asignat, id_mat, id_alumno, id_profesor) VALUES (:id_asignat, :id_mat, :id_alumno, :id_profesor)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'id_asignat' => $id_asignat,
            'id_mat' => $id_mat,
            'id_alumno' => $id_alumno,
            'id_profesor' => $id_profesor
        ]);

        echo "<p style='color:green;'>Asignación de materia registrada con éxito.</p>";
    }
} catch (PDOException $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Asignación de Materias</title>
</head>
<body>
    <figure class="blockquote text-center">
        <blockquote>
    <style>
        body{font-family:Arial;margin:30px;background: #fff5f7;color: #333}
        form{max-width:500px;background:#fff;padding:25px;border-radius:8px;box-shadow:0 0 10px #f8d7da;border-top:4px solid #d3690d}
        input,select{width:100%;padding:8px;margin:5px 0 15px;box-sizing:border-box;border:1px solid #f1b0b7;border-radius:4px}
        button{padding:10px 20px;background: #d3690d;color:#fff;border:none;border-radius:5px;cursor:pointer;font-weight:bold}
        button:hover{background: #d3690d}
        a{color: #bcd30d;text-decoration:none;display:inline-block;margin-top:10px;font-weight:bold}
        a:hover{text-decoration: underline;color: #a61e4d}
        p{padding:10px;border-radius:4px;font-weight:bold}
        .ok{background: #d4edda;color: #155724}
        .err{background: #f8d7da;color: #721c24}
    </style>
</head>
<figure class="blockquote text-center">
    <blockquote>
        <h2>Asignación de Materias</h2>
    </blockquote>
</figure>

    <form method="POST">
        <label>ID Asignación (id-asignat):</label><br>
        <input type="text" name="id_asignat" required><br><br>

        <label>ID Materia (id-mat):</label><br>
        <input type="text" name="id_mat" required><br><br>
        
        <label>ID Alumno:</label><br>
        <input type="text" name="id_alumno" required><br><br>

        <label>ID Profesor:</label><br>
        <input type="text" name="id_profesor" required><br><br>
        
        <button type="submit">Asignar Materia</button>
        
    </blockquote>
</figure>
    </form>
</body>
</html>