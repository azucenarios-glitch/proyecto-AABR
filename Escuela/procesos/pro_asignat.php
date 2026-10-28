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