<?php
require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR', 'ALUMNO']);

function h($t) { return htmlspecialchars((string)$t, ENT_QUOTES, "UTF-8"); }

$exito = "";
$error = "";
$actualizado = false;
$soloAlumno = esAlumno();
$idAlumnoSesion = $soloAlumno ? (int)$_SESSION["id_alumno"] : 0;

// ---------- ASIGNAR ----------
if (isset($_POST["guardar"])) {
    $idMateria  = (int)$_POST["id_materia"];
    $idAlumno   = $soloAlumno ? $idAlumnoSesion : (int)$_POST["id_alumno"];
    $idProfesor = (int)$_POST["id_profesor"];

    try {
        $sql = "INSERT INTO asignatura (ID_MATERIAS, ID_ALUMNOS, ID_PROFESORES) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iii", $idMateria, $idAlumno, $idProfesor);
        $stmt->execute();
        $stmt->close();
        $exito = $soloAlumno ? "Materia inscrita correctamente." : "Asignatura guardada correctamente.";
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error = $soloAlumno ? "Ya tienes inscrita esa materia." : "Ese alumno ya tiene asignada esa materia.";
        } else {
            $error = "Ocurrió un error al guardar: " . $e->getMessage();
        }
    }
}

// ---------- MODIFICAR ----------
if (isset($_POST["actualizar"])) {
    $idAsignatura = (int)$_POST["id_asignatura"];
    $idMateria    = (int)$_POST["id_materia"];
    $idAlumno     = $soloAlumno ? $idAlumnoSesion : (int)$_POST["id_alumno"];
    $idProfesor   = (int)$_POST["id_profesor"];

    try {
        if ($soloAlumno) {
            $sql = "UPDATE asignatura SET ID_MATERIAS = ?, ID_PROFESORES = ? WHERE ID_ASIGNATURA = ? AND ID_ALUMNOS = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iiii", $idMateria, $idProfesor, $idAsignatura, $idAlumnoSesion);
        } else {
            $sql = "UPDATE asignatura SET ID_MATERIAS = ?, ID_ALUMNOS = ?, ID_PROFESORES = ? WHERE ID_ASIGNATURA = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("iiii", $idMateria, $idAlumno, $idProfesor, $idAsignatura);
        }
        $stmt->execute();
        $stmt->close();
        $exito = "Asignatura actualizada correctamente.";
        $actualizado = true;
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error = $soloAlumno ? "Ya tienes inscrita esa materia." : "Ese alumno ya tiene asignada esa materia.";
        } else {
            $error = "Ocurrió un error al actualizar: " . $e->getMessage();
        }
    }
}

// ---------- ELIMINAR ----------
if (isset($_POST["eliminar"])) {
    $idAsignatura = (int)$_POST["id_asignatura"];

    try {
        if ($soloAlumno) {
            $stmt = $conexion->prepare("DELETE FROM asignatura WHERE ID_ASIGNATURA = ? AND ID_ALUMNOS = ?");
            $stmt->bind_param("ii", $idAsignatura, $idAlumnoSesion);
        } else {
            $stmt = $conexion->prepare("DELETE FROM asignatura WHERE ID_ASIGNATURA = ?");
            $stmt->bind_param("i", $idAsignatura);
        }
        $stmt->execute();
        $stmt->close();
        $exito = "Asignatura eliminada.";
    } catch (mysqli_sql_exception $e) {
        $error = "Ocurrió un error al eliminar: " . $e->getMessage();
    }
}

// ---------- Si viene ?editar=ID, se carga la fila para llenar el formulario ----------
$editando = null;
if (isset($_GET["editar"]) && !$actualizado) {
    $idEdit = (int)$_GET["editar"];
    if ($soloAlumno) {
        $stmt = $conexion->prepare("SELECT * FROM asignatura WHERE ID_ASIGNATURA = ? AND ID_ALUMNOS = ?");
        $stmt->bind_param("ii", $idEdit, $idAlumnoSesion);
    } else {
        $stmt = $conexion->prepare("SELECT * FROM asignatura WHERE ID_ASIGNATURA = ?");
        $stmt->bind_param("i", $idEdit);
    }
    $stmt->execute();
    $fila = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $editando = $fila ?: null;
}

$materias = $conexion->query("SELECT ID_MATERIAS, CLAVE_MATERIA, NOMBRE_MATERIA, CREDITOS FROM materias WHERE ESTATUS = 'ALTA' ORDER BY NOMBRE_MATERIA")->fetch_all(MYSQLI_ASSOC);
$profesores = $conexion->query("SELECT ID_PROFESORES, CLAVE, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM profesores WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);

if ($soloAlumno) {
    $stmt = $conexion->prepare("SELECT ID_ALUMNOS, MATRICULA, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM alumnos WHERE ID_ALUMNOS = ?");
    $stmt->bind_param("i", $idAlumnoSesion);
    $stmt->execute();
    $alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT s.ID_ASIGNATURA, m.CLAVE_MATERIA, m.NOMBRE_MATERIA, m.CREDITOS,
                CONCAT(a.NOMBRE, ' ', a.APELLIDO_PATERNO) AS ALUMNO,
                CONCAT(p.NOMBRE, ' ', p.APELLIDO_PATERNO) AS PROFESOR
         FROM asignatura s
         INNER JOIN materias m ON m.ID_MATERIAS = s.ID_MATERIAS
         INNER JOIN alumnos a ON a.ID_ALUMNOS = s.ID_ALUMNOS
         INNER JOIN profesores p ON p.ID_PROFESORES = s.ID_PROFESORES
         WHERE s.ID_ALUMNOS = ?
         ORDER BY m.NOMBRE_MATERIA"
    );
    $stmt->bind_param("i", $idAlumnoSesion);
    $stmt->execute();
    $asignadas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $alumnos = $conexion->query("SELECT ID_ALUMNOS, MATRICULA, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM alumnos WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);

    $asignadas = $conexion->query(
        "SELECT s.ID_ASIGNATURA, m.CLAVE_MATERIA, m.NOMBRE_MATERIA, m.CREDITOS,
                CONCAT(a.NOMBRE, ' ', a.APELLIDO_PATERNO) AS ALUMNO,
                CONCAT(p.NOMBRE, ' ', p.APELLIDO_PATERNO) AS PROFESOR
         FROM asignatura s
         INNER JOIN materias m ON m.ID_MATERIAS = s.ID_MATERIAS
         INNER JOIN alumnos a ON a.ID_ALUMNOS = s.ID_ALUMNOS
         INNER JOIN profesores p ON p.ID_PROFESORES = s.ID_PROFESORES
         ORDER BY s.ID_ASIGNATURA DESC"
    )->fetch_all(MYSQLI_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $soloAlumno ? "Inscripción de materias" : "Asignación de materias" ?></title>
    <link href="<?= $base ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-main: #090512;
            --bg-panel: #130924;
            --bg-card: #1a0d30;
            --border-purple: #6f2dbd;
            --border-subtle: #3b1c6e;
            --accent-purple: #7c3aed;
            --accent-purple-hover: #6d28d9;
            --accent-blue: #2563eb;
            --accent-blue-hover: #1d4ed8;
            --text-main: #f3e8ff;
            --text-muted: #c4b5fd;
        }

        body.dark-school-theme {
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
        }

        .dark-school-theme .navbar,
        .dark-school-theme .dropdown-menu {
            background-color: var(--bg-panel) !important;
            border-color: var(--border-subtle) !important;
        }

        .dark-school-theme .nav-link,
        .dark-school-theme .navbar-brand,
        .dark-school-theme .dropdown-item {
            color: var(--text-main) !important;
        }

        .dark-school-theme .dropdown-item:hover,
        .dark-school-theme .nav-link.active {
            background-color: var(--accent-purple) !important;
            color: #ffffff !important;
        }

        .form-shell {
            background-color: var(--bg-panel);
            border: 1.5px solid var(--border-purple);
            border-radius: 14px;
            box-shadow: 0 0 28px rgba(111, 45, 189, 0.22);
            overflow: hidden;
        }

        .form-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-subtle);
            color: #ffffff;
            text-align: center;
            padding: 1.25rem 1rem;
        }

        .form-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.75rem;
        }

        .form-body {
            padding: 1.75rem;
        }

        .form-label {
            color: var(--text-main);
            font-size: 0.92rem;
        }

        .form-control,
        .form-select {
            background-color: var(--bg-main);
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
        }

        .form-control:focus,
        .form-select:focus {
            background-color: var(--bg-main);
            border-color: var(--accent-purple);
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.25);
        }

        .form-control[readonly] {
            background-color: var(--bg-card);
            color: var(--text-muted);
        }

        .btn-purple {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
            color: #ffffff;
            font-weight: 500;
        }

        .btn-purple:hover,
        .btn-purple:focus {
            background-color: var(--accent-purple-hover);
            border-color: var(--accent-purple-hover);
            color: #ffffff;
        }

        .btn-blue {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
            color: #ffffff;
            font-weight: 500;
        }

        .btn-blue:hover,
        .btn-blue:focus {
            background-color: var(--accent-blue-hover);
            border-color: var(--accent-blue-hover);
            color: #ffffff;
        }

        .btn-purple-outline {
            background-color: transparent;
            border-color: var(--accent-purple);
            color: #ddd6fe;
            font-weight: 500;
        }

        .btn-purple-outline:hover,
        .btn-purple-outline:focus {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
            color: #ffffff;
        }

        .table-dark-purple {
            --bs-table-bg: var(--bg-card);
            --bs-table-striped-bg: #21113d;
            --bs-table-hover-bg: #29154b;
            --bs-table-border-color: var(--border-subtle);
            --bs-table-color: var(--text-main);
            color: var(--text-main);
            border-color: var(--border-subtle);
        }

        .table-dark-purple thead th {
            background-color: #2e1065;
            color: #ffffff;
            border-color: var(--border-purple);
            font-weight: 600;
        }
    </style>
</head>
<body class="dark-school-theme">
<?php include __DIR__ . "/../../navbar/navbar.php"; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card form-shell">

                <div class="form-header">
                    <h2><?= $soloAlumno ? "Inscripción de materias (Alumno)" : "Asignación de materias" ?></h2>
                </div>

                <div class="form-body">

                    <?php if ($exito !== "") { ?>
                        <div class="alert alert-success"><?= h($exito) ?></div>
                    <?php } ?>
                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php } ?>

                    <?php if ($editando !== null) { ?>
                        <div class="alert alert-warning">Modificando la asignatura #<?= h($editando["ID_ASIGNATURA"]) ?></div>
                    <?php } ?>

                    <form method="POST">

                        <?php if ($editando !== null) { ?>
                            <input type="hidden" name="id_asignatura" value="<?= (int)$editando["ID_ASIGNATURA"] ?>">
                        <?php } ?>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Materia</label>
                                <select name="id_materia" class="form-select" required>
                                    <option value="">Selecciona una materia...</option>
                                    <?php foreach ($materias as $m) { ?>
                                        <option value="<?= (int)$m["ID_MATERIAS"] ?>" <?= ($editando !== null && (int)$editando["ID_MATERIAS"] === (int)$m["ID_MATERIAS"]) ? "selected" : "" ?>>
                                            <?= h($m["CLAVE_MATERIA"] . " - " . $m["NOMBRE_MATERIA"] . " (" . $m["CREDITOS"] . " créd.)") ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Profesor</label>
                                <select name="id_profesor" class="form-select" required>
                                    <option value="">Selecciona un profesor...</option>
                                    <?php foreach ($profesores as $p) { ?>
                                        <option value="<?= (int)$p["ID_PROFESORES"] ?>" <?= ($editando !== null && (int)$editando["ID_PROFESORES"] === (int)$p["ID_PROFESORES"]) ? "selected" : "" ?>>
                                            <?= h($p["CLAVE"] . " - " . $p["NOMBRE_COMPLETO"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <?php if ($soloAlumno) {
                            $miAlumno = $alumnos[0] ?? null;
                        ?>
                            <div class="mb-4">
                                <label class="form-label">Alumno</label>
                                <input type="text" class="form-control" readonly
                                       value="<?= h(($miAlumno["MATRICULA"] ?? "") . " - " . ($miAlumno["NOMBRE_COMPLETO"] ?? "")) ?>">
                            </div>
                        <?php } else { ?>
                            <div class="mb-4">
                                <label class="form-label">Alumno</label>
                                <select name="id_alumno" class="form-select" required>
                                    <option value="">Selecciona un alumno...</option>
                                    <?php foreach ($alumnos as $a) { ?>
                                        <option value="<?= (int)$a["ID_ALUMNOS"] ?>" <?= ($editando !== null && (int)$editando["ID_ALUMNOS"] === (int)$a["ID_ALUMNOS"]) ? "selected" : "" ?>>
                                            <?= h($a["ID_ALUMNOS"] . " - " . $a["MATRICULA"] . " - " . $a["NOMBRE_COMPLETO"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                        <?php } ?>

                        <div class="d-flex flex-wrap gap-2">
                            <?php if ($editando !== null) { ?>
                                <button type="submit" name="actualizar" class="btn btn-purple">Guardar cambios</button>
                                <a href="asignacion_materias.php" class="btn btn-purple-outline">Cancelar</a>
                            <?php } else { ?>
                                <button type="submit" name="guardar" class="btn btn-purple">
                                    <?= $soloAlumno ? "Meter materia" : "Asignar materia" ?>
                                </button>
                            <?php } ?>
                        </div>

                    </form>

                    <hr style="border-color: var(--border-subtle);" class="my-4">

                    <h4 class="mb-3" style="color: #ffffff;"><?= $soloAlumno ? "Mis materias inscritas" : "Asignaturas registradas" ?></h4>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-dark-purple align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Clave</th>
                                    <th>Materia</th>
                                    <th>Créditos</th>
                                    <?php if (!$soloAlumno) { ?><th>Alumno</th><?php } ?>
                                    <th>Profesor</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($asignadas) === 0) { ?>
                                    <tr>
                                        <td colspan="<?= $soloAlumno ? 5 : 6 ?>" class="text-center py-3" style="color: var(--text-muted);">
                                            Aún no hay asignaciones.
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php foreach ($asignadas as $s) { ?>
                                    <tr>
                                        <td><?= h($s["CLAVE_MATERIA"]) ?></td>
                                        <td><?= h($s["NOMBRE_MATERIA"]) ?></td>
                                        <td><?= h($s["CREDITOS"]) ?></td>
                                        <?php if (!$soloAlumno) { ?><td><?= h($s["ALUMNO"]) ?></td><?php } ?>
                                        <td><?= h($s["PROFESOR"]) ?></td>
                                        <td class="text-nowrap">
                                            <div class="d-flex flex-wrap gap-1">
                                                <a href="asignacion_materias.php?editar=<?= (int)$s["ID_ASIGNATURA"] ?>" class="btn btn-sm btn-blue">Modificar</a>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta materia?');">
                                                    <input type="hidden" name="id_asignatura" value="<?= (int)$s["ID_ASIGNATURA"] ?>">
                                                    <button type="submit" name="eliminar" class="btn btn-sm btn-purple-outline">Eliminar</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>