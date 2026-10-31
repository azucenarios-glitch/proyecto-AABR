<?php
// Incluimos la conexión subiendo un nivel desde la carpeta procesos/
include '../conexion.php';

$mensaje = "";

// 1. Guardar la calificación si se envía el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['guardar_calificacion'])) {
    $id_alumno = $_POST['id_alumno'];
    $cal = $_POST['cal'];
    $id_unidad = $_POST['id_unidad'];

    try {
        $sql = "INSERT INTO CAPCAL (id_alumno, cal, id_unidad) VALUES (:id_alumno, :cal, :id_unidad)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute([
            'id_alumno' => $id_alumno,
            'cal' => $cal,
            'id_unidad' => $id_unidad
        ]);
        $mensaje = "¡Calificación guardada correctamente!";
    } catch (PDOException $e) {
        $mensaje = "Error al guardar calificación: " . $e->getMessage();
    }
}

// 2. Obtener lista de unidades para el selector (<select>)
$unidades = $conexion->query("SELECT * FROM unidad")->fetchAll(PDO::FETCH_ASSOC);

// 3. CONSULTA PARA MOSTRAR LAS CALIFICACIONES REGISTRADAS
// Hacemos JOIN con la tabla 'unidad' para traer la descripción de la unidad
$sqlConsulta = "SELECT c.id_capcal, c.id_alumno, c.cal, u.descripcion 
                FROM CAPCAL c 
                INNER JOIN unidad u ON c.id_unidad = u.id_unidad 
                ORDER BY c.id_capcal DESC";
$calificaciones = $conexion->query($sqlConsulta)->fetchAll(PDO::FETCH_ASSOC);
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
<head>
    <meta charset="UTF-8">
    <title>Captura de Calificaciones</title>
</head>
<body>
<head>
    <figure class="blockquote text-center">
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
</form>
</body>
</html>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Captura de Calificaciones</title>
</head>
<body>
    <h2>Captura de Calificaciones</h2>

    <?php if (!empty($mensaje)): ?>
        <p style="color: green;"><strong><?php echo $mensaje; ?></strong></p>
    <?php endif; ?>

    <!-- Formulario para ingresar calificaciones -->
    <form method="POST" action="">
        <label>ID / Matrícula del Alumno:</label><br>
        <input type="text" name="id_alumno" required style="padding: 5px; width: 250px;"><br><br>

        <label>Unidad:</label><br>
        <select name="id_unidad" required style="padding: 5px; width: 260px;">
            <option value="">-- Seleccione una Unidad --</option>
            <?php foreach ($unidades as $u): ?>
                <option value="<?php echo $u['id_unidad']; ?>">
                    <?php echo $u['id_unidad'] . " - " . htmlspecialchars($u['descripcion']); ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label>Calificación:</label><br>
        <input type="number" step="0.01" min="0" max="100" name="cal" required style="padding: 5px; width: 250px;"><br><br>

        <button type="submit" name="guardar_calificacion" style="padding: 8px 15px;">Guardar Calificación</button>
    </form>

    <hr>

    <!-- AQUÍ SE VEN LAS CALIFICACIONES -->
    <h3>Calificaciones Registradas</h3>
    <table border="1" cellpadding="8" cellspacing="0" style="border-collapse: collapse; width: 80%;">
        <thead>
            <tr style="background-color: #f2f2f2;">
                <th>ID Registro</th>
                <th>ID Alumno</th>
                <th>Unidad</th>
                <th>Calificación</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($calificaciones) > 0): ?>
                <?php foreach ($calificaciones as $row): ?>
                <tr>
                    <td style="text-align: center;"><?php echo $row['id_capcal']; ?></td>
                    <td><?php echo htmlspecialchars($row['id_alumno']); ?></td>
                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                    <td style="text-align: center;"><strong><?php echo number_format($row['cal'], 2); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">No hay calificaciones registradas aún.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
