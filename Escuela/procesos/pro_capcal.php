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
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <link href="https://getbootstrap.com/docs/5.3/assets/css/docs.css" rel="stylesheet"/>
    <title>Bootstrap Example</title>
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  </head>
  <body class="p-3 m-0 border-0 bd-example m-0 border-0">
    <!-- Example Code Start-->
    <ul class="nav nav-pills">
      <li class="nav-item">
        <a class="nav-link active" aria-current="false" href="../index.php">← Inicio</a>
      </li>
      <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Baja</a>
        <ul class="dropdown-menu" style="">
          <li><a class="dropdown-item" href='../Baja/ba_grupos.php'>Grupos</a></li>
          <li><a class="dropdown-item" href='../Baja/ba_materias.php'>Materia</a></li>
          <li><a class="dropdown-item" href='../Baja/ba_profesores.php'>Profesor</a></li>
          <li><a class="dropdown-item" href='../Baja/ba_alumnos.php'>Alumnos</a></li>
        </ul>
      </li>
      <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Procesos</a>
          <ul class="dropdown-menu" style="">
            <li><a class="dropdown-item" href='../procesos/pro_alumnos.php'>Alumnos</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_grupos.php'>Grupos</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_materias.php'>Lista</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_profesores.php'>Profesores</a></li>
        </ul>
      </li>
        <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Registro</a>
        <ul class="dropdown-menu" style="">
          <li><a class="dropdown-item" href='../registro/reg_materias.php'>Materia</a><li>
          <li><a class="dropdown-item" href='../registro/reg_profesores.php'>Profesores</a></li>
          <li><a class="dropdown-item" href='../registro/reg_grupos.php'>Grupo</a><li>
        </ul>
      </li>
      <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Reporte</a>
        <ul class="dropdown-menu" style="">
          <li><a class="dropdown-item" href='../reporter/rep_alumnos.php'>Alumnos</a><li>
          <li><a class="dropdown-item" href='../reportes/rep_profesor.php'>Profesores</a></li>
          <li><a class="dropdown-item" href='../reportes/rep_materias.php'>Materia</a></li>
          <li><a class="dropdown-item" href='../reportes/rep_grupos.php'>Grupos</a></li>
        </ul>
      </li>
    </ul>
    <!-- Example Code End -->
  </body>
</html>
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