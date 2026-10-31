<?php
// Incluimos la conexión a la base de datos
include '../conexion.php'; 

$mensaje = "";
$tipo_alerta = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_in = $_POST['id_in'];
    $id_grupo = $_POST['id_grupo'];
    $id_alumno = $_POST['id_alumno'];
    $status_ins = "ACTA"; // Requerido por la rúbrica

    try {
        $sql = "INSERT INTO insgrup (id_in, id_grupo, id_alumno, status_ins) VALUES (:id_in, :id_grupo, :id_alumno, :status_ins)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            ':id_in' => $id_in,
            ':id_grupo' => $id_grupo,
            ':id_alumno' => $id_alumno,
            ':status_ins' => $status_ins
        ]);
        $mensaje = "¡Inscripción a grupo registrada con éxito!";
        $tipo_alerta = "ok";
    } catch (PDOException $e) {
        $mensaje = "Error al registrar la inscripción: " . $e->getMessage();
        $tipo_alerta = "err";
    }
}
?>
 
<!DOCTYPE html>
<html lang="en">
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
        <a class="nav-link active" aria-current="false" href="../index.php">Inicio</a>
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
            <li><a class="dropdown-item" href='../procesos/pro_materias.php'>Materias</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_profesores.php'>Profesores</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_capcal.php'>Capcal</a></li>
            <li><a class="dropdown-item" href='../procesos/pro_asignat.php'>Asignat </a></li>
        </ul>
      </li>
        <li class="nav-item dropdown">
        <a
          class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false"
          >Registro</a>
        <ul class="dropdown-menu" style="">
          <li><a class="dropdown-item" href='../registro/reg_materias.php'>Materia</a><li>
          <li><a class="dropdown-item" href='../registro/reg_profesores.php'>Profesore</a></li>
          <li><a class="dropdown-item" href='../registro/reg_grupos.php'>Grupo</a><li>
          <li><a class="dropdown-item" href='../registro/reg_alumno.php'>Alumno</a><li>
          <li><a class="dropdown-item" href='../registro/reg_unidad.php'>Unidad</a><li>
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

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"/>
    <title>Inscripción a Grupos - Escuela</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fff5f7; color: #333; }
        .container-form { max-width: 500px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px #f8d7da; border-top: 4px solid #bcd30d; margin-top: 20px; }
        input, select { width: 100%; padding: 8px; margin: 5px 0 15px; box-sizing: border-box; border: 1px solid #f1b0b7; border-radius: 4px; }
        button { padding: 10px 20px; background: #bcd30d; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; width: 100%; }
        button:hover { background: #a6be0b; }
        p { padding: 10px; border-radius: 4px; font-weight: bold; }
        .ok { background: #d4edda; color: #155724; }
        .err { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body class="p-3 m-0 border-0">
    <!-- Contenedor del Formulario -->
    <div class="container-form">
        <figure class="text-center">
            <h2 style="color: #8fa00a;">Inscripción a Grupos</h2>
        </figure>

        <?php if($mensaje != ""): ?>
            <p class="<?php echo $tipo_alerta; ?>"><?php echo $mensaje; ?></p>
        <?php endif; ?>

        <form action="pro_insgrup.php" method="POST">
            <label class="fw-bold">ID Inscripción (id-in):</label>
            <input type="text" name="id_in" required placeholder="Ej. 1">

            <label class="fw-bold">ID Grupo:</label>
            <input type="text" name="id_grupo" required placeholder="Ej. GRP101">

            <label class="fw-bold">ID Alumno:</label>
            <input type="text" name="id_alumno" required placeholder="Ej. ALUM01">

            <button type="submit">Inscribir</button>
        </form>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>