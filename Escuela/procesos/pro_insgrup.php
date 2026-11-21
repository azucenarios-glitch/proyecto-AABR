<?php
$servidor = "localhost";
$usuario = "root";
$clave = "";
$base = "Escuela";

$conexion = mysqli_connect($servidor, $usuario, $clave, $base);
if (!$conexion) {
    die("Conexión fallida: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");

$mensaje = "";
$tipo_alerta = "success";

// Cambia 'id_alumno' si tu tabla 'alumnos' usa otro nombre de columna clave (ej. matricula, clave)
$col_alumno = "id_alumno";

// Proceso de inscripción o cambio de grupo vía POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_alumno = isset($_POST["id_alumno"]) ? (int)$_POST["id_alumno"] : 0;
    $id_grupo  = isset($_POST["id_grupo"]) ? (int)$_POST["id_grupo"] : 0;

    if ($id_alumno > 0 && $id_grupo > 0) {
        $sql_update = "UPDATE alumnos SET id_grupo = ? WHERE {$col_alumno} = ? AND estatus = 1";
        $stmt = mysqli_prepare($conexion, $sql_update);
        mysqli_stmt_bind_param($stmt, "ii", $id_grupo, $id_alumno);

        if (mysqli_stmt_execute($stmt) && mysqli_stmt_affected_rows($stmt) > 0) {
            $mensaje = "Inscripción / cambio de grupo actualizado correctamente.";
            $tipo_alerta = "success";
        } else {
            $mensaje = "No se pudo actualizar el grupo. Verifica que el alumno esté activo y el grupo exista.";
            $tipo_alerta = "warning";
        }
        mysqli_stmt_close($stmt);
    } else {
        $mensaje = "Selecciona un alumno y un grupo válidos.";
        $tipo_alerta = "danger";
    }
}

// Consulta de alumnos activos
$alumnos = mysqli_query(
    $conexion,
    "SELECT {$col_alumno}, apaterno, amaterno, nombre, id_grupo FROM alumnos WHERE estatus = 1 ORDER BY apaterno, amaterno, nombre"
);

// Consulta de grupos disponibles
$grupos = mysqli_query(
    $conexion,
    "SELECT * FROM grupos"
);

$lista_grupos = [];
if ($grupos) {
    while ($g = mysqli_fetch_assoc($grupos)) {
        $lista_grupos[] = $g;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos — Inscripción a Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fff5f7; color: #333; }
        h2 { color: #d63384; margin-top: 20px; }
        .card-form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.06); max-width: 720px; }
        table { background: #fff; margin-top: 20px; }
    </style>
</head>
<body class="p-3 m-0">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link" href="../index.php">Inicio</a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Baja</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../baja/ba_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="../baja/ba_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="../baja/ba_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../baja/ba_profesores.php">Profesor</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Procesos</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../procesos/pro_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_materias.php">Materias</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_asignat.php">Asignat</a></li>
                <li><a class="dropdown-item active" href="../procesos/pro_insgrup.php">Insgrup</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Registro</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../registro/reg_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../registro/reg_profesores.php">Profesor</a></li>
                <li><a class="dropdown-item" href="../registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item" href="../registro/reg_alumno.php">Alumno</a></li>
                <li><a class="dropdown-item" href="../registro/reg_unidad.php">Unidad</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Reporte</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../reportes/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_grupos.php">Grupos</a></li>
            </ul>
        </li>
    </ul>

    <h2>Inscripción a Grupo</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_alerta, ENT_QUOTES, 'UTF-8') ?> mt-3" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card-form mt-3">
        <form method="POST" action="pro_insgrup.php" class="row g-3">
            <div class="col-md-6">
                <label for="id_alumno" class="form-label">Alumno activo</label>
                <select name="id_alumno" id="id_alumno" class="form-select" required>
                    <option value="">Selecciona un alumno...</option>
                    <?php if ($alumnos): ?>
                        <?php while ($a = mysqli_fetch_assoc($alumnos)): ?>
                            <?php $valAlumno = (int)$a[$col_alumno]; ?>
                            <option value="<?= $valAlumno ?>">
                                <?= htmlspecialchars($a['apaterno'] . ' ' . $a['amaterno'] . ', ' . $a['nombre'] . ' (Grupo actual: ' . ($a['id_grupo'] ?? 'Sin asignar') . ')', ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-6">
                <label for="id_grupo" class="form-label">Grupo destino</label>
                <select name="id_grupo" id="id_grupo" class="form-select" required>
                    <option value="">Selecciona un grupo...</option>
                    <?php foreach ($lista_grupos as $g): ?>
                        <?php
                        $idG = (int)($g['id_grupo'] ?? $g['id'] ?? 0);
                        $etiquetaGrupo = $g['nombre'] ?? $g['clave'] ?? $g['grupo'] ?? ("Grupo #" . $idG);
                        ?>
                        <?php if ($idG > 0): ?>
                            <option value="<?= $idG ?>">
                                <?= htmlspecialchars($etiquetaGrupo, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Guardar inscripción</button>
            </div>
        </form>
    </div>

    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Alumno</th>
                <th>Grupo Actual</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $res_tabla = mysqli_query(
                $conexion,
                "SELECT {$col_alumno}, apaterno, amaterno, nombre, id_grupo FROM alumnos WHERE estatus = 1 ORDER BY apaterno, amaterno, nombre"
            );
            if ($res_tabla && mysqli_num_rows($res_tabla) > 0):
                while ($row = mysqli_fetch_assoc($res_tabla)):
            ?>
                <tr>
                    <td><?= (int)$row[$col_alumno] ?></td>
                    <td><?= htmlspecialchars($row['apaterno'] . ' ' . $row['amaterno'] . ', ' . $row['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($row['id_grupo'] ?? 'Sin asignar'), ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php
                endwhile;
            else:
            ?>
                <tr>
                    <td colspan="3" class="text-center text-muted">No hay alumnos activos registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>