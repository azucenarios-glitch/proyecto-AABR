<?php
require_once __DIR__ . "/../../config/conexion.php";
require_once __DIR__ . "/rep_helper.php";
requerirRol(['ADMINISTRADOR', 'MAESTRO']);

$idGrupo = (int)($_GET["id_grupo"] ?? 0);
$soloMaestro = esMaestro();
$idProfesorSesion = $soloMaestro ? (int)$_SESSION["id_profesor"] : 0;

if ($soloMaestro) {
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
    $grupos = $conexion->query("SELECT ID_GRUPOS, NOMBRE_GRUPO FROM grupos WHERE ESTATUS = 'ALTA' ORDER BY NOMBRE_GRUPO")->fetch_all(MYSQLI_ASSOC);
}

$grupo = null;
$filas = [];

if ($idGrupo > 0) {
    $permitido = true;
    if ($soloMaestro) {
        $permitido = false;
        foreach ($grupos as $gItem) {
            if ((int)$gItem["ID_GRUPOS"] === $idGrupo) {
                $permitido = true;
                break;
            }
        }
    }

    if ($permitido) {
        $stmt = $conexion->prepare("SELECT ID_GRUPOS, NOMBRE_GRUPO, TURNO, SEMESTRE, DESCRIPCION FROM grupos WHERE ID_GRUPOS = ?");
        $stmt->bind_param("i", $idGrupo);
        $stmt->execute();
        $grupo = $stmt->get_result()->fetch_assoc() ?: null;
        $stmt->close();
    }

    if ($grupo !== null) {
        if ($soloMaestro) {
            $stmt = $conexion->prepare(
                "SELECT a.MATRICULA, a.APELLIDO_PATERNO, a.APELLIDO_MATERNO, a.NOMBRE, a.CORREO, a.TELEFONO,
                        GROUP_CONCAT(DISTINCT m.NOMBRE_MATERIA ORDER BY m.NOMBRE_MATERIA SEPARATOR ', ') AS MATERIAS
                 FROM grupo_alumno ga
                 INNER JOIN alumnos a ON a.ID_ALUMNOS = ga.ID_ALUMNOS
                 INNER JOIN asignatura s ON s.ID_ALUMNOS = a.ID_ALUMNOS AND s.ID_PROFESORES = ?
                 INNER JOIN materias m ON m.ID_MATERIAS = s.ID_MATERIAS
                 WHERE ga.ID_GRUPOS = ? AND ga.ESTATUS = 'ALTA' AND a.ESTATUS = 'ALTA'
                 GROUP BY a.ID_ALUMNOS, a.MATRICULA, a.APELLIDO_PATERNO, a.APELLIDO_MATERNO, a.NOMBRE, a.CORREO, a.TELEFONO
                 ORDER BY a.APELLIDO_PATERNO, a.APELLIDO_MATERNO, a.NOMBRE"
            );
            $stmt->bind_param("ii", $idProfesorSesion, $idGrupo);
        } else {
            $stmt = $conexion->prepare(
                "SELECT a.MATRICULA, a.APELLIDO_PATERNO, a.APELLIDO_MATERNO, a.NOMBRE, a.CORREO, a.TELEFONO
                 FROM grupo_alumno ga
                 INNER JOIN alumnos a ON a.ID_ALUMNOS = ga.ID_ALUMNOS
                 WHERE ga.ID_GRUPOS = ? AND ga.ESTATUS = 'ALTA' AND a.ESTATUS = 'ALTA'
                 ORDER BY a.APELLIDO_PATERNO, a.APELLIDO_MATERNO, a.NOMBRE"
            );
            $stmt->bind_param("i", $idGrupo);
        }
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}

// ---------- Descarga en Excel ----------
if (isset($_GET["descargar"]) && $grupo !== null && count($filas) > 0) {
    $datos = [];
    $datos[] = ["Lista de alumnos por grupo"];
    $datos[] = ["Grupo: " . $grupo["NOMBRE_GRUPO"] . "   Turno: " . $grupo["TURNO"] . "   Semestre: " . $grupo["SEMESTRE"]];
    $datos[] = [];
    if ($soloMaestro) {
        $datos[] = ["NO.", "MATRICULA", "APELLIDO PATERNO", "APELLIDO MATERNO", "NOMBRE", "CORREO", "TELEFONO", "MATERIAS"];
        foreach ($filas as $n => $f) {
            $datos[] = [$n + 1, $f["MATRICULA"], $f["APELLIDO_PATERNO"], $f["APELLIDO_MATERNO"], $f["NOMBRE"], $f["CORREO"], $f["TELEFONO"], $f["MATERIAS"]];
        }
    } else {
        $datos[] = ["NO.", "MATRICULA", "APELLIDO PATERNO", "APELLIDO MATERNO", "NOMBRE", "CORREO", "TELEFONO"];
        foreach ($filas as $n => $f) {
            $datos[] = [$n + 1, $f["MATRICULA"], $f["APELLIDO_PATERNO"], $f["APELLIDO_MATERNO"], $f["NOMBRE"], $f["CORREO"], $f["TELEFONO"]];
        }
    }
    enviarExcel($datos, "alumnos_grupo_" . nombreArchivo($grupo["NOMBRE_GRUPO"]), 3, [0, 1]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $soloMaestro ? "Mis grupos" : "Reporte: alumnos por grupo" ?></title>
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

        @media print {
            nav, .no-imprimir { display: none !important; }
            body.dark-school-theme { background: #ffffff !important; color: #000000 !important; }
            .form-shell { background: #ffffff !important; border: none !important; box-shadow: none !important; }
            .form-header { background: #ffffff !important; color: #000000 !important; border-bottom: 2px solid #000000 !important; }
            .table-dark-purple,
            .table-dark-purple th,
            .table-dark-purple td {
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
                    <h2><?= $soloMaestro ? "Mis grupos y alumnos" : "Lista de alumnos por grupo" ?></h2>
                </div>

                <div class="form-body">

                    <?php if ($soloMaestro && count($grupos) === 0) { ?>
                        <div class="alert alert-info">Aún no tienes grupos con alumnos asignados.</div>
                    <?php } ?>

                    <form method="GET" class="row g-3 mb-4 no-imprimir">
                        <div class="col-md-8">
                            <label class="form-label"><?= $soloMaestro ? "Mis grupos" : "Grupo" ?></label>
                            <select name="id_grupo" class="form-select" required>
                                <option value="">Selecciona un grupo...</option>
                                <?php foreach ($grupos as $g) { ?>
                                    <option value="<?= (int)$g["ID_GRUPOS"] ?>" <?= (int)$idGrupo === (int)$g["ID_GRUPOS"] ? "selected" : "" ?>>
                                        <?= h($g["NOMBRE_GRUPO"]) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-blue w-100">Ver lista</button>
                        </div>
                    </form>

                    <?php if ($idGrupo > 0 && $grupo === null) { ?>
                        <div class="alert alert-warning">No tienes acceso a ese grupo o no se encontró.</div>
                    <?php } ?>

                    <?php if ($grupo !== null) { ?>
                        <hr style="border-color: var(--border-subtle);" class="my-4 no-imprimir">

                        <div class="mb-3">
                            <h5 class="mb-1" style="color: #ffffff;">Grupo <?= h($grupo["NOMBRE_GRUPO"]) ?></h5>
                            <div style="color: var(--text-muted);">
                                Turno: <?= h($grupo["TURNO"]) ?> &nbsp;|&nbsp;
                                Semestre: <?= h($grupo["SEMESTRE"]) ?> &nbsp;|&nbsp;
                                Total de alumnos: <strong style="color: #ffffff;"><?= count($filas) ?></strong>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped table-hover table-dark-purple align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Matrícula</th>
                                        <th>Apellido paterno</th>
                                        <th>Apellido materno</th>
                                        <th>Nombre</th>
                                        <th>Correo</th>
                                        <th>Teléfono</th>
                                        <?php if ($soloMaestro) { ?><th>Materias contigo</th><?php } ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($filas) === 0) { ?>
                                        <tr>
                                            <td colspan="<?= $soloMaestro ? 8 : 7 ?>" class="text-center py-3" style="color: var(--text-muted);">
                                                Este grupo no tiene alumnos inscritos.
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php foreach ($filas as $n => $f) { ?>
                                        <tr>
                                            <td><?= $n + 1 ?></td>
                                            <td><?= h($f["MATRICULA"]) ?></td>
                                            <td><?= h($f["APELLIDO_PATERNO"]) ?></td>
                                            <td><?= h($f["APELLIDO_MATERNO"]) ?></td>
                                            <td><?= h($f["NOMBRE"]) ?></td>
                                            <td><?= h($f["CORREO"]) ?></td>
                                            <td><?= h($f["TELEFONO"]) ?></td>
                                            <?php if ($soloMaestro) { ?><td><?= h($f["MATERIAS"]) ?></td><?php } ?>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if (count($filas) > 0) { ?>
                            <div class="d-flex flex-wrap gap-2 no-imprimir">
                                <a href="?id_grupo=<?= (int)$idGrupo ?>&descargar=1" class="btn btn-purple">Descargar Excel</a>
                                <button type="button" class="btn btn-purple-outline" onclick="window.print()">Imprimir</button>
                            </div>
                        <?php } ?>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>