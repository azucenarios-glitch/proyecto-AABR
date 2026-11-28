<?php
require_once __DIR__ . "/../../config/conexion.php";
require_once __DIR__ . "/rep_helper.php";
requerirRol(['ADMINISTRADOR', 'ALUMNO']);

$soloAlumno = esAlumno();
$idAlumno = $soloAlumno ? (int)$_SESSION["id_alumno"] : (int)($_GET["id_alumno"] ?? 0);

$alumnos = $soloAlumno
    ? []
    : $conexion->query("SELECT ID_ALUMNOS, MATRICULA, CONCAT(APELLIDO_PATERNO, ' ', APELLIDO_MATERNO, ' ', NOMBRE) AS NOMBRE_COMPLETO FROM alumnos WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, APELLIDO_MATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);

$alumno = null;
$materias = [];
$gruposAlumno = [];
$totalCreditos = 0;

if ($idAlumno > 0) {
    $stmt = $conexion->prepare("SELECT ID_ALUMNOS, MATRICULA, CONCAT(APELLIDO_PATERNO, ' ', APELLIDO_MATERNO, ' ', NOMBRE) AS NOMBRE_COMPLETO FROM alumnos WHERE ID_ALUMNOS = ?");
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $alumno = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($alumno !== null) {
        $stmt = $conexion->prepare(
            "SELECT m.CLAVE_MATERIA, m.NOMBRE_MATERIA, m.CREDITOS,
                    CONCAT(p.NOMBRE, ' ', p.APELLIDO_PATERNO, ' ', p.APELLIDO_MATERNO) AS PROFESOR
             FROM asignatura s
             INNER JOIN materias m ON m.ID_MATERIAS = s.ID_MATERIAS
             INNER JOIN profesores p ON p.ID_PROFESORES = s.ID_PROFESORES
             WHERE s.ID_ALUMNOS = ?
             ORDER BY m.NOMBRE_MATERIA"
        );
        $stmt->bind_param("i", $idAlumno);
        $stmt->execute();
        $materias = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($materias as $m) {
            $totalCreditos += (int)$m["CREDITOS"];
        }

        $stmt = $conexion->prepare("SELECT g.NOMBRE_GRUPO FROM grupo_alumno ga INNER JOIN grupos g ON g.ID_GRUPOS = ga.ID_GRUPOS WHERE ga.ID_ALUMNOS = ? AND ga.ESTATUS = 'ALTA' ORDER BY g.NOMBRE_GRUPO");
        $stmt->bind_param("i", $idAlumno);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $g) {
            $gruposAlumno[] = $g["NOMBRE_GRUPO"];
        }
        $stmt->close();
    }
}

$textoGrupos = count($gruposAlumno) > 0 ? implode(", ", $gruposAlumno) : "Sin grupo";

// ---------- Descarga en Excel ----------
if (isset($_GET["descargar"]) && $alumno !== null && count($materias) > 0) {
    $datos = [];
    $datos[] = ["Lista de materias por alumno"];
    $datos[] = ["Alumno: " . $alumno["MATRICULA"] . " - " . $alumno["NOMBRE_COMPLETO"]];
    $datos[] = ["Grupo: " . $textoGrupos];
    $datos[] = [];
    $datos[] = ["NO.", "CLAVE", "MATERIA", "CREDITOS", "PROFESOR"];
    foreach ($materias as $n => $m) {
        $datos[] = [$n + 1, $m["CLAVE_MATERIA"], $m["NOMBRE_MATERIA"], (int)$m["CREDITOS"], $m["PROFESOR"]];
    }
    $datos[] = ["", "", "TOTAL DE CREDITOS", $totalCreditos, ""];
    enviarExcel($datos, "materias_alumno_" . nombreArchivo($alumno["MATRICULA"]), 4, [0, 1, 2]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte: materias por alumno</title>
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
                    <h2>Lista de materias por alumno</h2>
                </div>

                <div class="form-body">

                    <?php if (!$soloAlumno) { ?>
                        <form method="GET" class="row g-3 mb-4 no-imprimir">
                            <div class="col-md-8">
                                <label class="form-label">Alumno</label>
                                <select name="id_alumno" class="form-select" required>
                                    <option value="">Selecciona un alumno...</option>
                                    <?php foreach ($alumnos as $a) { ?>
                                        <option value="<?= (int)$a["ID_ALUMNOS"] ?>" <?= (int)$idAlumno === (int)$a["ID_ALUMNOS"] ? "selected" : "" ?>>
                                            <?= h($a["MATRICULA"] . " - " . $a["NOMBRE_COMPLETO"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-blue w-100">Ver lista</button>
                            </div>
                        </form>
                    <?php } ?>

                    <?php if ($idAlumno > 0 && $alumno === null) { ?>
                        <div class="alert alert-warning">No se encontró el alumno seleccionado.</div>
                    <?php } ?>

                    <?php if ($alumno !== null) { ?>
                        <?php if (!$soloAlumno) { ?><hr style="border-color: var(--border-subtle);" class="my-4 no-imprimir"><?php } ?>

                        <div class="mb-3">
                            <h5 class="mb-1" style="color: #ffffff;"><?= h($alumno["NOMBRE_COMPLETO"]) ?></h5>
                            <div style="color: var(--text-muted);">
                                Matrícula: <?= h($alumno["MATRICULA"]) ?> &nbsp;|&nbsp;
                                Grupo: <?= h($textoGrupos) ?> &nbsp;|&nbsp;
                                Materias: <strong style="color: #ffffff;"><?= count($materias) ?></strong>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered table-striped table-hover table-dark-purple align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>No.</th>
                                        <th>Clave</th>
                                        <th>Materia</th>
                                        <th>Créditos</th>
                                        <th>Profesor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($materias) === 0) { ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3" style="color: var(--text-muted);">
                                                Este alumno no tiene materias asignadas.
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php foreach ($materias as $n => $m) { ?>
                                        <tr>
                                            <td><?= $n + 1 ?></td>
                                            <td><?= h($m["CLAVE_MATERIA"]) ?></td>
                                            <td><?= h($m["NOMBRE_MATERIA"]) ?></td>
                                            <td><?= h($m["CREDITOS"]) ?></td>
                                            <td><?= h($m["PROFESOR"]) ?></td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <?php if (count($materias) > 0) { ?>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total de créditos</th>
                                            <th colspan="2"><?= (int)$totalCreditos ?></th>
                                        </tr>
                                    </tfoot>
                                <?php } ?>
                            </table>
                        </div>

                        <?php if (count($materias) > 0) { ?>
                            <div class="d-flex flex-wrap gap-2 no-imprimir">
                                <a href="<?= $soloAlumno ? "?descargar=1" : "?id_alumno=" . (int)$idAlumno . "&descargar=1" ?>" class="btn btn-purple">Descargar Excel</a>
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