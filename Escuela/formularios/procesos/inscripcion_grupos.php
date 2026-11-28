<?php
require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR']);

function h($t) { return htmlspecialchars((string)$t); }

$exito = "";
$error = "";

// ---------- INSCRIBIR ----------
if (isset($_POST["guardar"])) {
    $idGrupo  = (int)$_POST["id_grupo"];
    $idAlumno = (int)$_POST["id_alumno"];
    $estatus  = isset($_POST["activo"]) ? "ALTA" : "BAJA";

    try {
        $sql = "INSERT INTO grupo_alumno (ID_GRUPOS, ID_ALUMNOS, ESTATUS) VALUES (?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iis", $idGrupo, $idAlumno, $estatus);
        $stmt->execute();
        $stmt->close();
        $exito = "Alumno inscrito al grupo correctamente.";
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error = "Ese alumno ya está inscrito en ese grupo.";
        } else {
            $error = "Ocurrió un error al guardar: " . $e->getMessage();
        }
    }
}

// ---------- CAMBIAR ESTATUS (dar de baja / reactivar) ----------
if (isset($_POST["cambiar_estatus"])) {
    $idGrupo  = (int)$_POST["id_grupo"];
    $idAlumno = (int)$_POST["id_alumno"];
    $nuevo    = ($_POST["estatus_actual"] === "ALTA") ? "BAJA" : "ALTA";

    try {
        $stmt = $conexion->prepare("UPDATE grupo_alumno SET ESTATUS = ? WHERE ID_GRUPOS = ? AND ID_ALUMNOS = ?");
        $stmt->bind_param("sii", $nuevo, $idGrupo, $idAlumno);
        $stmt->execute();
        $stmt->close();
        $exito = "Estatus actualizado a " . $nuevo . ".";
    } catch (mysqli_sql_exception $e) {
        $error = "Ocurrió un error al actualizar: " . $e->getMessage();
    }
}

// ---------- ELIMINAR INSCRIPCIÓN ----------
if (isset($_POST["eliminar"])) {
    $idGrupo  = (int)$_POST["id_grupo"];
    $idAlumno = (int)$_POST["id_alumno"];

    try {
        $stmt = $conexion->prepare("DELETE FROM grupo_alumno WHERE ID_GRUPOS = ? AND ID_ALUMNOS = ?");
        $stmt->bind_param("ii", $idGrupo, $idAlumno);
        $stmt->execute();
        $stmt->close();
        $exito = "Inscripción eliminada.";
    } catch (mysqli_sql_exception $e) {
        $error = "Ocurrió un error al eliminar: " . $e->getMessage();
    }
}

$grupos = $conexion->query("SELECT ID_GRUPOS, NOMBRE_GRUPO FROM grupos WHERE ESTATUS = 'ALTA' ORDER BY NOMBRE_GRUPO")->fetch_all(MYSQLI_ASSOC);
$alumnos = $conexion->query("SELECT ID_ALUMNOS, MATRICULA, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM alumnos WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);

$inscritos = $conexion->query(
    "SELECT ga.ID_GRUPOS, g.NOMBRE_GRUPO, ga.ID_ALUMNOS, a.MATRICULA,
            CONCAT(a.NOMBRE, ' ', a.APELLIDO_PATERNO, ' ', a.APELLIDO_MATERNO) AS NOMBRE_COMPLETO,
            ga.ESTATUS
     FROM grupo_alumno ga
     INNER JOIN grupos g ON g.ID_GRUPOS = ga.ID_GRUPOS
     INNER JOIN alumnos a ON a.ID_ALUMNOS = ga.ID_ALUMNOS
     ORDER BY g.NOMBRE_GRUPO, a.APELLIDO_PATERNO"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscripción de alumnos a grupos</title>
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

        .form-label,
        .form-check-label {
            color: var(--text-main);
            font-size: 0.92rem;
        }

        .form-select {
            background-color: var(--bg-main);
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
        }

        .form-select:focus {
            background-color: var(--bg-main);
            border-color: var(--accent-purple);
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.25);
        }

        .form-check-input {
            background-color: var(--bg-main);
            border-color: var(--border-purple);
        }

        .form-check-input:checked {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
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
        <div class="col-lg-11">
            <div class="card form-shell">

                <div class="form-header">
                    <h2>Inscripción de alumnos a grupos</h2>
                </div>

                <div class="form-body">

                    <?php if ($exito !== "") { ?>
                        <div class="alert alert-success"><?= h($exito) ?></div>
                    <?php } ?>
                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php } ?>

                    <form method="POST">

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Id grupo</label>
                                <select name="id_grupo" class="form-select" required>
                                    <option value="">Selecciona un grupo...</option>
                                    <?php foreach ($grupos as $g) { ?>
                                        <option value="<?= (int)$g["ID_GRUPOS"] ?>"><?= h($g["ID_GRUPOS"] . " - " . $g["NOMBRE_GRUPO"]) ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Id alumno</label>
                                <select name="id_alumno" class="form-select" required>
                                    <option value="">Selecciona un alumno...</option>
                                    <?php foreach ($alumnos as $a) { ?>
                                        <option value="<?= (int)$a["ID_ALUMNOS"] ?>"><?= h($a["ID_ALUMNOS"] . " - " . $a["MATRICULA"] . " - " . $a["NOMBRE_COMPLETO"]) ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" name="activo" id="activo" checked>
                            <label class="form-check-label" for="activo">Estatus activo</label>
                        </div>

                        <button type="submit" name="guardar" class="btn btn-purple">Inscribir alumno</button>

                    </form>

                    <hr style="border-color: var(--border-subtle);" class="my-4">

                    <h4 class="mb-3" style="color: #ffffff;">Alumnos inscritos</h4>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-dark-purple align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Id grupo</th>
                                    <th>Grupo</th>
                                    <th>Id alumno</th>
                                    <th>Matrícula</th>
                                    <th>Alumno</th>
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($inscritos) === 0) { ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-3" style="color: var(--text-muted);">
                                            Aún no hay inscripciones.
                                        </td>
                                    </tr>
                                <?php } ?>
                                <?php foreach ($inscritos as $i) { ?>
                                    <tr>
                                        <td><?= (int)$i["ID_GRUPOS"] ?></td>
                                        <td><?= h($i["NOMBRE_GRUPO"]) ?></td>
                                        <td><?= (int)$i["ID_ALUMNOS"] ?></td>
                                        <td><?= h($i["MATRICULA"]) ?></td>
                                        <td><?= h($i["NOMBRE_COMPLETO"]) ?></td>
                                        <td>
                                            <span class="badge <?= $i["ESTATUS"] === "ALTA" ? "bg-success" : "bg-secondary" ?>"><?= h($i["ESTATUS"]) ?></span>
                                        </td>
                                        <td class="text-nowrap">
                                            <div class="d-flex flex-wrap gap-1">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="id_grupo" value="<?= (int)$i["ID_GRUPOS"] ?>">
                                                    <input type="hidden" name="id_alumno" value="<?= (int)$i["ID_ALUMNOS"] ?>">
                                                    <input type="hidden" name="estatus_actual" value="<?= h($i["ESTATUS"]) ?>">
                                                    <button type="submit" name="cambiar_estatus" class="btn btn-sm btn-blue">
                                                        <?= $i["ESTATUS"] === "ALTA" ? "Dar de baja" : "Reactivar" ?>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar esta inscripción?');">
                                                    <input type="hidden" name="id_grupo" value="<?= (int)$i["ID_GRUPOS"] ?>">
                                                    <input type="hidden" name="id_alumno" value="<?= (int)$i["ID_ALUMNOS"] ?>">
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