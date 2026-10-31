


<?php
// Incluimos la conexión (asegúrate de que conexion.php esté en la ruta correcta o ajusta la ruta)
include '../conexion.php'; // Usa 'conexion.php' si está en la misma carpeta, o '../conexion.php' si está un nivel arriba.

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $descripcion = $_POST['descripcion'];

    try {
        $sql = "INSERT INTO unidad (descripcion) VALUES (:descripcion)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([':descripcion' => $descripcion]);
        $mensaje = "¡Unidad registrada correctamente!";
    } catch (PDOException $e) {
        $mensaje = "Error al registrar la unidad: " . $e->getMessage();
    }
}
?>



<body>
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
            <li><a class="dropdown-item" href='../procesos/pro_insgrup.php'>Insgrup</a></li>
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
            <figure class="blockquote text-center">
                <blockquote>
                    <meta charset='UTF-8'>
                    <title>Unidad</title>
                    <style>
                    body { font-family: Arial, sans-serif; margin: 40px; background-color: #f4f6f9; }
        .container { max-width: 500px; background: white; padding: 25px; border-radius: 8px; box-shadow: 0px 4px 10px rgba(0,0,0,0.1); }
        h2 { color: #333; }
        label { display: block; margin-top: 10px; font-weight: bold; }
        input { width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box; }
        button { margin-top: 15px; background: #28a745; color: white; border: none; padding: 10px; width: 100%; border-radius: 4px; cursor: pointer; }
        button:hover { background: #218838; }
        .msg { margin-top: 15px; color: green; font-weight: bold; }
                    </style>
                    </head>
                </blockquote>
            </figure>
            <div class="container">
    <h2>Registro / UNIDAD</h2>
    <?php if($mensaje != "") echo "<p class='msg'>$mensaje</p>"; ?>
    
    <form action="reg_unidad.php" method="POST">
        <label for="descripcion">Descripción de la Unidad:</label>
        <input type="text" name="descripcion" required placeholder="Ej. Unidad 1: Fundamentos">

        <button type="submit">Guardar Unidad</button>
    </form>
</div>
    </body>
</html> 
