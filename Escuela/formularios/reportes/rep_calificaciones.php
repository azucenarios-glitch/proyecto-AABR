<?php
require_once __DIR__ . "/../../config/conexion.php";
require_once __DIR__ . "/rep_helper.php";
requerirRol(['ADMINISTRADOR', 'MAESTRO']);

$idMateria = (int)($_GET["id_materia"] ?? $_POST["id_materia"] ?? 0);
$idGrupo   = (int)($_GET["id_grupo"] ?? $_POST["id_grupo"] ?? 0);
$soloMaestro = esMaestro();
$idProfesorSesion = $soloMaestro ? (int)$_SESSION["id_profesor"] : 0;
$mensajeExito = "";
$errorCaptura = "";

if ($soloMaestro) {
    $stmt = $conexion->prepare(
        "SELECT DISTINCT m.ID_MATERIAS, m.CLAVE_MATERIA, m.NOMBRE_MATERIA
         FROM materias m
         INNER JOIN asignatura s ON s.ID_MATERIAS = m.ID_MATERIAS
         WHERE m.ESTATUS = 'ALTA' AND s.ID_PROFESORES = ?
         ORDER BY m.NOMBRE_MATERIA"
    );
    $stmt->bind_param("i", $idProfesorSesion);
    $stmt->execute();
    $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $stmt = $conexion->prepare(
        "SELECT DISTINCT g.ID_GRUPOS, g.NOMBRE_GRUPO
         FROM grupos g
         INNER JOIN grupo_alumno ga ON ga.ID_GRUPOS = g.ID_GRUPOS AND ga.ESTATUS = 'ALTA'
         INNER JOIN asignatura s ON s.ID_ALUMNOS = ga.ID_ALUMNOS
         WHERE g.ESTATUS = 'ALTA' AND s.ID_PROFESORES = ?
         ORDER BY g.NOMBRE_GRUPO"
    );
    $stmt->bind_param("i", $idProfesorSesion);
    $stmt->execute();
    $grupos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $materias = $conexion->query("SELECT ID_MATERIAS, CLAVE_MATERIA, NOMBRE_MATERIA FROM materias WHERE ESTATUS = 'ALTA' ORDER BY NOMBRE_MATERIA")->fetch_all(MYSQLI_ASSOC);
    $grupos   = $conexion->query("SELECT ID_GRUPOS, NOMBRE_GRUPO FROM grupos WHERE ESTATUS = 'ALTA' ORDER BY NOMBRE_GRUPO")->fetch_all(MYSQLI_ASSOC);
}
$unidades = $conexion->query("SELECT ID_UNIDAD, DESCRIPCION FROM unidad WHERE ESTATUS = 'ALTA' ORDER BY ID_UNIDAD")->fetch_all(MYSQLI_ASSOC);

$materia = null;
$grupo = null;
$filas = [];
$notas = [];

if ($idMateria > 0 && $idGrupo > 0) {
    $permitido = true;
    if ($soloMaestro) {
        $matOk = false;
        foreach ($materias as $mItem) {
            if ((int)$mItem["ID_MATERIAS"] === $idMateria) { $matOk = true; break; }
        }
        $grpOk = false;
        foreach ($grupos as $gItem) {
            if ((int)$gItem["ID_GRUPOS"] === $idGrupo) { $grpOk = true; break; }
        }
        $permitido = $matOk && $grpOk;
    }

    if ($permitido) {
        $stmt = $conexion->prepare("SELECT ID_MATERIAS, CLAVE_MATERIA, NOMBRE_MATERIA FROM materias WHERE ID_MATERIAS = ?");
        $stmt->bind_param("i", $idMateria);
        $stmt->execute();
        $materia = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();

        $stmt = $conexion->prepare("SELECT ID_GRUPOS, NOMBRE_GRUPO FROM grupos WHERE ID_GRUPOS = ?");
        $stmt->bind_param("i", $idGrupo);
        $stmt->execute();
        $grupo = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }

    if ($materia !== null && $grupo !== null) {
        if ($soloMaestro) {
            $stmt = $conexion->prepare(
                "SELECT a.ID_ALUMNOS, a.MATRICULA,
                        CONCAT(a.APELLIDO_PATERNO, ' ', a.APELLIDO_MATERNO, ' ', a.NOMBRE) AS NOMBRE_COMPLETO
                 FROM grupo_alumno ga
                 INNER JOIN alumnos a ON a.ID_ALUMNOS = ga.ID_ALUMNOS AND a.ESTATUS = 'ALTA'
                 INNER JOIN asignatura s ON s.ID_ALUMNOS = a.ID_ALUMNOS AND s.ID_MATERIAS = ? AND s.ID_PROFESORES = ?
                 WHERE ga.ID_GRUPOS = ? AND ga.ESTATUS = 'ALTA'
                 ORDER BY a.APELLIDO_PATERNO, a.APELLIDO_MATERNO, a.NOMBRE"
            );
            $stmt->bind_param("iii", $idMateria, $idProfesorSesion, $idGrupo);
        } else {
            $stmt = $conexion->prepare(
                "SELECT a.ID_ALUMNOS, a.MATRICULA,
                        CONCAT(a.APELLIDO_PATERNO, ' ', a.APELLIDO_MATERNO, ' ', a.NOMBRE) AS NOMBRE_COMPLETO
                 FROM grupo_alumno ga
                 INNER JOIN alumnos a ON a.ID_ALUMNOS = ga.ID_ALUMNOS AND a.ESTATUS = 'ALTA'
                 INNER JOIN asignatura s ON s.ID_ALUMNOS = a.ID_ALUMNOS AND s.ID_MATERIAS = ?
                 WHERE ga.ID_GRUPOS = ? AND ga.ESTATUS = 'ALTA'
                 ORDER BY a.APELLIDO_PATERNO, a.APELLIDO_MATERNO, a.NOMBRE"
            );
            $stmt->bind_param("ii", $idMateria, $idGrupo);
        }
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_calificaciones"])) {
            $calificacionesPost = $_POST["cal"] ?? [];
            $validas = true;

            foreach ($filas as $f) {
                $idAlu = (int)$f["ID_ALUMNOS"];
                foreach ($unidades as $u) {
                    $idUni = (int)$u["ID_UNIDAD"];
                    $raw = trim((string)($calificacionesPost[$idAlu][$idUni] ?? ""));
                    if ($raw !== "") {
                        if (!is_numeric($raw) || (float)$raw < 0 || (float)$raw > 100) {
                            $validas = false;
                            $errorCaptura = "Las calificaciones deben ser números entre 0 y 100.";
                            break 2;
                        }
                    }
                }
            }

            if ($validas) {
                try {
                    $conexion->begin_transaction();

                    $stmtSelect = $conexion->prepare(
                        "SELECT ID_CALIFICACIONES FROM calificaciones WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ? AND ID_UNIDAD = ? LIMIT 1"
                    );
                    $stmtUpdate = $conexion->prepare(
                        "UPDATE calificaciones SET CALIFICACION = ? WHERE ID_CALIFICACIONES = ?"
                    );
                    $stmtInsert = $conexion->prepare(
                        "INSERT INTO calificaciones (ID_ALUMNOS, ID_MATERIAS, ID_UNIDAD, CALIFICACION) VALUES (?, ?, ?, ?)"
                    );
                    $stmtDelete = $conexion->prepare(
                        "DELETE FROM calificaciones WHERE ID_CALIFICACIONES = ?"
                    );

                    foreach ($filas as $f) {
                        $idAlu = (int)$f["ID_ALUMNOS"];
                        foreach ($unidades as $u) {
                            $idUni = (int)$u["ID_UNIDAD"];
                            $raw = trim((string)($calificacionesPost[$idAlu][$idUni] ?? ""));

                            $stmtSelect->bind_param("iii", $idAlu, $idMateria, $idUni);
                            $stmtSelect->execute();
                            $existente = $stmtSelect->get_result()->fetch_assoc();

                            if ($raw === "") {
                                if ($existente) {
                                    $idCal = (int)$existente["ID_CALIFICACIONES"];
                                    $stmtDelete->bind_param("i", $idCal);
                                    $stmtDelete->execute();
                                }
                            } else {
                                $valCal = round((float)$raw, 2);
                                if ($existente) {
                                    $idCal = (int)$existente["ID_CALIFICACIONES"];
                                    $stmtUpdate->bind_param("di", $valCal, $idCal);
                                    $stmtUpdate->execute();
                                } else {
                                    $stmtInsert->bind_param("iiid", $idAlu, $idMateria, $idUni, $valCal);
                                    $stmtInsert->execute();
                                }
                            }
                        }
                    }

                    $stmtSelect->close();
                    $stmtUpdate->close();
                    $stmtInsert->close();
                    $stmtDelete->close();
                    $conexion->commit();
                    $mensajeExito = "Calificaciones guardadas correctamente.";
                } catch (mysqli_sql_exception $e) {
                    $conexion->rollback();
                    $errorCaptura = "Error al guardar calificaciones: " . $e->getMessage();
                }
            }
        }

        $stmt = $conexion->prepare("SELECT ID_ALUMNOS, ID_UNIDAD, CALIFICACION FROM calificaciones WHERE ID_MATERIAS = ?");
        $stmt->bind_param("i", $idMateria);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $c) {
            $notas[$c["ID_ALUMNOS"]][$c["ID_UNIDAD"]] = (float)$c["CALIFICACION"];
        }
        $stmt->close();
    }
}

// ---------- Promedios ----------
$promAlumno = [];
$promUnidad = [];
foreach ($filas as $f) {
    $valores = [];
    foreach ($unidades as $u) { $valores[] = $notas[$f["ID_ALUMNOS"]][$u["ID_UNIDAD"]] ?? null; }
    $promAlumno[$f["ID_ALUMNOS"]] = promedio($valores);
}
foreach ($unidades as $u) {
    $valores = [];
    foreach ($filas as $f) { $valores[] = $notas[$f["ID_ALUMNOS"]][$u["ID_UNIDAD"]] ?? null; }
    $promUnidad[$u["ID_UNIDAD"]] = promedio($valores);
}
$promGrupo = promedio(array_values($promAlumno));

// ---------- Descarga en Excel ----------
if (isset($_GET["descargar"]) && $materia !== null && $grupo !== null && count($filas) > 0) {
    $datos = [];
    $datos[] = ["Lista de calificaciones por materia y grupo"];
    $datos[] = ["Materia: " . $materia["CLAVE_MATERIA"] . " - " . $materia["NOMBRE_MATERIA"]];
    $datos[] = ["Grupo: " . $grupo["NOMBRE_GRUPO"]];
    $datos[] = [];

    $encabezado = ["NO.", "MATRICULA", "ALUMNO"];
    foreach ($unidades as $u) { $encabezado[] = "UNIDAD " . $u["ID_UNIDAD"]; }
    $encabezado[] = "PROMEDIO";
    $datos[] = $encabezado;

    foreach ($filas as $n => $f) {
        $fila = [$n + 1, $f["MATRICULA"], $f["NOMBRE_COMPLETO"]];
        foreach ($unidades as $u) { $fila[] = $notas[$f["ID_ALUMNOS"]][$u["ID_UNIDAD"]] ?? ""; }
        $fila[] = $promAlumno[$f["ID_ALUMNOS"]] ?? "";
        $datos[] = $fila;
    }

    $pie = ["", "", "PROMEDIO DEL GRUPO"];
    foreach ($unidades as $u) { $pie[] = $promUnidad[$u["ID_UNIDAD"]] ?? ""; }
    $pie[] = $promGrupo ?? "";
    $datos[] = $pie;

    enviarExcel($datos, "calificaciones_" . nombreArchivo($materia["CLAVE_MATERIA"]) . "_" . nombreArchivo($grupo["NOMBRE_GRUPO"]), 4, [0, 1, 2]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: calificaciones por materia y grupo</title>
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

        .form-select,
        .cal-input {
            background-color: var(--bg-main);
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
        }

        .form-select:focus,
        .cal-input:focus {
            background-color: var(--bg-main);
            border-color: var(--accent-purple);
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.25);
        }

        .cal-input {
            width: 82px;
            margin: 0 auto;
            text-align: center;
            padding: 0.25rem 0.4rem;
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

        .btn-blue-outline {
            background-color: transparent;
            border-color: var(--accent-blue);
            color: #93c5fd;
            font-weight: 500;
        }

        .btn-blue-outline:hover,
        .btn-blue-outline:focus {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
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

        .table-dark-purple tfoot tr,
        .table-dark-purple tfoot th {
            background-color: #241247;
            color: #ffffff;
            border-color: var(--border-purple);
        }

        @media print {
            nav, .no-imprimir { display: none !important; }
            body.dark-school-theme { background: #ffffff !important; color: #000000 !important; }
            .form-shell { background: #ffffff !important; border: none !important; box-shadow: none !important; }
            .form-header { background: #ffffff !important; color: #000000 !important; border-bottom: 2px solid #000000 !important; }
            .table-dark-purple,
            .table-dark-purple th,
            .table-dark-purple td,
            .table-dark-purple tfoot tr,
            .table-dark-purple tfoot th {
                background: #ffffff !important;
                color: #000000 !important;
                border-color: #000000 !important;
            }
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
                    <h2>Lista de calificaciones por materia y grupo</h2>
                </div>

                <div class="form-body">

                    <form method="GET" class="row g-3 align-items-end mb-4 no-imprimir">
                        <div class="col-sm-6 col-md-5">
                            <label class="form-label">Materia</label>
                            <select name="id_materia" class="form-select" required>
                                <option value="">Selecciona una materia...</option>
                                <?php foreach ($materias as $m) { ?>
                                    <option value="<?= (int)$m["ID_MATERIAS"] ?>" <?= (int)$idMateria === (int)$m["ID_MATERIAS"] ? "selected" : "" ?>>
                                        <?= h($m["CLAVE_MATERIA"] . " - " . $m["NOMBRE_MATERIA"]) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <label class="form-label">Grupo</label>
                            <select name="id_grupo" class="form-select" required>
                                <option value="">Selecciona un grupo...</option>
                                <?php foreach ($grupos as $g) { ?>
                                    <option value="<?= (int)$g["ID_GRUPOS"] ?>" <?= (int)$idGrupo === (int)$g["ID_GRUPOS"] ? "selected" : "" ?>>
                                        <?= h($g["NOMBRE_GRUPO"]) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <button type="submit" class="btn btn-blue w-100">Ver lista</button>
                        </div>
                    </form>

                    <?php if ($mensajeExito !== "") { ?>
                        <div class="alert alert-success no-imprimir"><?= h($mensajeExito) ?></div>
                    <?php } ?>

                    <?php if ($errorCaptura !== "") { ?>
                        <div class="alert alert-danger no-imprimir"><?= h($errorCaptura) ?></div>
                    <?php } ?>

                    <?php if ($idMateria > 0 && $idGrupo === 0) { ?>
                        <div class="alert alert-info no-imprimir">Elige un grupo para ver la lista de esa materia.</div>
                    <?php } elseif ($idGrupo > 0 && $idMateria === 0) { ?>
                        <div class="alert alert-info no-imprimir">Elige una materia para ver la lista de ese grupo.</div>
                    <?php } ?>

                    <?php if ($idMateria > 0 && $idGrupo > 0 && ($materia === null || $grupo === null)) { ?>
                        <div class="alert alert-warning">No tienes acceso a esa materia o grupo, o no se encontró.</div>
                    <?php } ?>

                    <?php if ($materia !== null && $grupo !== null) { ?>
                        <hr style="border-color: var(--border-subtle);" class="my-4 no-imprimir">

                        <div class="mb-3">
                            <h5 class="mb-1" style="color: #ffffff;"><?= h($materia["CLAVE_MATERIA"] . " - " . $materia["NOMBRE_MATERIA"]) ?></h5>
                            <div style="color: var(--text-muted);">
                                Grupo: <?= h($grupo["NOMBRE_GRUPO"]) ?> &nbsp;|&nbsp;
                                Alumnos: <strong style="color: #ffffff;"><?= count($filas) ?></strong>
                            </div>
                        </div>

                        <form method="POST">
                            <input type="hidden" name="id_materia" value="<?= (int)$idMateria ?>">
                            <input type="hidden" name="id_grupo" value="<?= (int)$idGrupo ?>">

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-striped table-hover table-dark-purple align-middle text-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>No.</th>
                                            <th>Matrícula</th>
                                            <th class="text-start">Alumno</th>
                                            <?php foreach ($unidades as $u) { ?>
                                                <th title="<?= h($u["DESCRIPCION"]) ?>">Unidad <?= (int)$u["ID_UNIDAD"] ?></th>
                                            <?php } ?>
                                            <th>Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (count($filas) === 0) { ?>
                                            <tr>
                                                <td colspan="<?= count($unidades) + 4 ?>" class="text-center py-3" style="color: var(--text-muted);">
                                                    No hay alumnos de este grupo con esa materia asignada.
                                                </td>
                                            </tr>
                                        <?php } ?>
                                        <?php foreach ($filas as $n => $f) {
                                            $idAlu = (int)$f["ID_ALUMNOS"];
                                        ?>
                                            <tr>
                                                <td><?= $n + 1 ?></td>
                                                <td><?= h($f["MATRICULA"]) ?></td>
                                                <td class="text-start"><?= h($f["NOMBRE_COMPLETO"]) ?></td>
                                                <?php foreach ($unidades as $u) {
                                                    $idUni = (int)$u["ID_UNIDAD"];
                                                    $valActual = $notas[$idAlu][$idUni] ?? "";
                                                ?>
                                                    <td>
                                                        <input
                                                            type="number"
                                                            step="0.1"
                                                            min="0"
                                                            max="100"
                                                            name="cal[<?= $idAlu ?>][<?= $idUni ?>]"
                                                            value="<?= $valActual !== "" ? h(number_format((float)$valActual, 1, ".", "")) : "" ?>"
                                                            class="form-control form-control-sm cal-input"
                                                            placeholder="—"
                                                        >
                                                    </td>
                                                <?php } ?>
                                                <th><?= fmt($promAlumno[$idAlu]) ?></th>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                    <?php if (count($filas) > 0) { ?>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-end">Promedio del grupo</th>
                                                <?php foreach ($unidades as $u) { ?>
                                                    <th><?= fmt($promUnidad[$u["ID_UNIDAD"]]) ?></th>
                                                <?php } ?>
                                                <th><?= fmt($promGrupo) ?></th>
                                            </tr>
                                        </tfoot>
                                    <?php } ?>
                                </table>
                            </div>

                            <?php if (count($filas) > 0) { ?>
                                <div class="d-flex flex-wrap gap-2 no-imprimir">
                                    <button type="submit" name="guardar_calificaciones" class="btn btn-blue">Guardar calificaciones</button>
                                    <a href="?id_materia=<?= (int)$idMateria ?>&id_grupo=<?= (int)$idGrupo ?>&descargar=1" class="btn btn-purple">Descargar Excel</a>
                                    <button type="button" class="btn btn-purple-outline" onclick="window.print()">Imprimir</button>
                                    <a href="<?= $base ?>/formularios/procesos/captura_calificaciones.php?modo=masiva&id_materia=<?= (int)$idMateria ?>&id_grupo=<?= (int)$idGrupo ?>" class="btn btn-blue-outline">Ir a captura de calificaciones</a>
                                </div>
                            <?php } ?>
                        </form>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>