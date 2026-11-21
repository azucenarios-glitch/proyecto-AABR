<?php
$servidor = "localhost";
$usuario  = "root";
$clave    = "";
$base     = "escuela";

$conexion = mysqli_connect($servidor, $usuario, $clave, $base);
if (!$conexion) {
    die("Conexión fallida: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");

$mensaje     = "";
$tipo_alerta = "success";

// Eliminar una asignación por id_asigmat
if (isset($_GET["quitar"])) {
    $id_quitar = (int)$_GET["quitar"];
    if ($id_quitar > 0) {
        $stmt_del = mysqli_prepare($conexion, "DELETE FROM asigmat WHERE id_asigmat = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id_quitar);
        if (mysqli_stmt_execute($stmt_del)) {
            $mensaje     = "Asignación eliminada correctamente.";
            $tipo_alerta = "info";
        } else {
            $mensaje     = "No se pudo eliminar la asignación.";
            $tipo_alerta = "danger";
        }
        mysqli_stmt_close($stmt_del);
    }
}

// Procesar inscripción a materia vía POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $id_alumno   = isset($_POST["id_alumno"]) ? trim($_POST["id_alumno"]) : "";
    $id_mat      = isset($_POST["id_mat"]) ? trim($_POST["id_mat"]) : "";
    $id_profesor = isset($_POST["id_profesor"]) ? trim($_POST["id_profesor"]) : "";

    if ($id_alumno !== "" && $id_mat !== "") {
        // Evitar duplicados del mismo alumno en la misma materia
        $stmt_dup = mysqli_prepare(
            $conexion,
            "SELECT id_asigmat FROM asigmat WHERE id_alumno = ? AND id_mat = ?"
        );
        mysqli_stmt_bind_param($stmt_dup, "ss", $id_alumno, $id_mat);
        mysqli_stmt_execute($stmt_dup);
        $res_dup = mysqli_stmt_get_result($stmt_dup);
        $ya_existe = ($res_dup && mysqli_num_rows($res_dup) > 0);
        mysqli_stmt_close($stmt_dup);

        if ($ya_existe) {
            $mensaje     = "Este alumno ya está asignado a esa materia.";
            $tipo_alerta = "warning";
        } else {
            $stmt_ins = mysqli_prepare(
                $conexion,
                "INSERT INTO asigmat (id_mat, id_alumno, id_profesor) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_ins, "sss", $id_mat, $id_alumno, $id_profesor);

            if (mysqli_stmt_execute($stmt_ins)) {
                $mensaje     = "Materia inscrita correctamente en asigmat.";
                $tipo_alerta = "success";
            } else {
                $mensaje     = "Error al guardar la inscripción: " . mysqli_error($conexion);
                $tipo_alerta = "danger";
            }
            mysqli_stmt_close($stmt_ins);
        }
    } else {
        $mensaje     = "Selecciona un alumno y una materia válidos.";
        $tipo_alerta = "danger";
    }
}

// Consultar alumnos activos
$alumnos = mysqli_query(
    $conexion,
    "SELECT * FROM alumnos WHERE estatus = 1 ORDER BY apaterno, amaterno, nombre"
);

// Consultar materias
$materias = mysqli_query(
    $conexion,
    "SELECT * FROM materias"
);
$lista_materias = [];
if ($materias) {
    while ($m = mysqli_fetch_assoc($materias)) {
        $lista_materias[] = $m;
    }
}

// Consultar profesores (si existe la tabla)
$profesores = mysqli_query(
    $conexion,
    "SELECT * FROM profesores"
);
$lista_profesores = [];
if ($profesores) {
    while ($p = mysqli_fetch_assoc($profesores)) {
        $lista_profesores[] = $p;
    }
}

// Consultar registros actuales en asigmat
$sql_asigmat = "
    SELECT
        asg.id_asigmat,
        asg.id_mat,
        asg.id_alumno,
        asg.id_profesor,
        a.apaterno,
        a.amaterno,
        a.nombre AS nombre_alumno,
        m.*
    FROM asigmat asg
    LEFT JOIN alumnos a ON asg.id_alumno = a.id_alumno
    LEFT JOIN materias m ON asg.id_mat = m.id_mat
    ORDER BY asg.id_asigmat DESC
";
$inscritos = mysqli_query($conexion, $sql_asigmat);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos — Inscripción a Materias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fff5f7; color: #333; }
        h2, h3 { color: #d63384; margin-top: 20px; }
        .card-form { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.06); max-width: 860px; }
        table { background: #fff; margin-top: 15px; }
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
                <li><a class="dropdown-item active" href="../procesos/pro_asignat.php">Asignat</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_insgrup.php">Insgrup</a></li>
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

    <h2>Inscripción a Materias (asigmat)</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_alerta, ENT_QUOTES, 'UTF-8') ?> mt-3" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="card-form mt-3">
        <form method="POST" action="pro_asignat.php" class="row g-3">
            <div class="col-md-4">
                <label for="id_alumno" class="form-label">Alumno activo</label>
                <select name="id_alumno" id="id_alumno" class="form-select" required>
                    <option value="">Selecciona un alumno...</option>
                    <?php if ($alumnos): ?>
                        <?php while ($a = mysqli_fetch_assoc($alumnos)): ?>
                            <?php
                            $valAlu = (string)($a['id_alumno'] ?? $a['id'] ?? '');
                            $nomAlu = trim(($a['apaterno'] ?? '') . ' ' . ($a['amaterno'] ?? '') . ', ' . ($a['nombre'] ?? ''));
                            ?>
                            <?php if ($valAlu !== ''): ?>
                                <option value="<?= htmlspecialchars($valAlu, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($valAlu . ' - ' . $nomAlu, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endif; ?>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="id_mat" class="form-label">Materia</label>
                <select name="id_mat" id="id_mat" class="form-select" required>
                    <option value="">Selecciona una materia...</option>
                    <?php foreach ($lista_materias as $m): ?>
                        <?php
                        $valMat = (string)($m['id_mat'] ?? $m['id_materia'] ?? $m['id'] ?? '');
                        $nomMat = $m['nombre'] ?? $m['materia'] ?? $m['asignatura'] ?? $valMat;
                        ?>
                        <?php if ($valMat !== ''): ?>
                            <option value="<?= htmlspecialchars($valMat, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($valMat . ' - ' . $nomMat, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label for="id_profesor" class="form-label">Profesor (opcional)</label>
                <select name="id_profesor" id="id_profesor" class="form-select">
                    <option value="">Sin asignar / General</option>
                    <?php foreach ($lista_profesores as $p): ?>
                        <?php
                        $valProf = (string)($p['id_profesor'] ?? $p['id'] ?? '');
                        $nomProf = trim(($p['apaterno'] ?? '') . ' ' . ($p['amaterno'] ?? '') . ' ' . ($p['nombre'] ?? $valProf));
                        ?>
                        <?php if ($valProf !== ''): ?>
                            <option value="<?= htmlspecialchars($valProf, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($valProf . ' - ' . $nomProf, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-primary">Inscribir materia</button>
            </div>
        </form>
    </div>

    <h3>Registros en asigmat</h3>
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Alumno</th>
                <th>Materia</th>
                <th>Profesor</th>
                <th style="width: 90px; text-align: center;">Quitar</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($inscritos && mysqli_num_rows($inscritos) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($inscritos)): ?>
                    <?php
                    $alumnoTexto = trim(($row['apaterno'] ?? '') . ' ' . ($row['amaterno'] ?? '') . ', ' . ($row['nombre_alumno'] ?? ''));
                    if ($alumnoTexto === ',') {
                        $alumnoTexto = $row['id_alumno'];
                    }
                    $materiaTexto = $row['nombre'] ?? $row['materia'] ?? $row['asignatura'] ?? $row['id_mat'];
                    ?>
                    <tr>
                        <td><?= (int)$row['id_asigmat'] ?></td>
                        <td><?= htmlspecialchars($row['id_alumno'] . ' — ' . $alumnoTexto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($row['id_mat'] . ' — ' . $materiaTexto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($row['id_profesor'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;">
                            <a href="?quitar=<?= (int)$row['id_asigmat'] ?>"
                               class="btn btn-sm btn-danger"
                               title="Quitar registro"
                               onclick="return confirm('¿Deseas quitar este registro de asigmat?')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center text-muted">No hay registros en asigmat todavía.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>