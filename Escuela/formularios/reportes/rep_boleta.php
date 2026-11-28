<?php
require_once __DIR__ . "/../../config/conexion.php";
require_once __DIR__ . "/rep_helper.php";
requerirRol(['ADMINISTRADOR', 'ALUMNO']);

date_default_timezone_set("America/Mexico_City");

$soloAlumno = esAlumno();
$idAlumno = $soloAlumno ? (int)$_SESSION["id_alumno"] : (int)($_GET["id_alumno"] ?? 0);

$alumnos = $soloAlumno
    ? []
    : $conexion->query("SELECT ID_ALUMNOS, MATRICULA, CONCAT(APELLIDO_PATERNO, ' ', APELLIDO_MATERNO, ' ', NOMBRE) AS NOMBRE_COMPLETO FROM alumnos WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, APELLIDO_MATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);
$unidades = $conexion->query("SELECT ID_UNIDAD, DESCRIPCION FROM unidad WHERE ESTATUS = 'ALTA' ORDER BY ID_UNIDAD")->fetch_all(MYSQLI_ASSOC);

$alumno = null;
$materias = [];
$notas = [];
$gruposAlumno = [];

if ($idAlumno > 0) {
    $stmt = $conexion->prepare("SELECT ID_ALUMNOS, MATRICULA, CONCAT(APELLIDO_PATERNO, ' ', APELLIDO_MATERNO, ' ', NOMBRE) AS NOMBRE_COMPLETO FROM alumnos WHERE ID_ALUMNOS = ?");
    $stmt->bind_param("i", $idAlumno);
    $stmt->execute();
    $alumno = $stmt->get_result()->fetch_assoc() ?: null;
    $stmt->close();

    if ($alumno !== null) {
        $stmt = $conexion->prepare(
            "SELECT m.ID_MATERIAS, m.CLAVE_MATERIA, m.NOMBRE_MATERIA,
                    CONCAT(p.NOMBRE, ' ', p.APELLIDO_PATERNO) AS PROFESOR
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

        $stmt = $conexion->prepare("SELECT ID_MATERIAS, ID_UNIDAD, CALIFICACION FROM calificaciones WHERE ID_ALUMNOS = ?");
        $stmt->bind_param("i", $idAlumno);
        $stmt->execute();
        foreach ($stmt->get_result()->fetch_all(MYSQLI_ASSOC) as $c) {
            $notas[$c["ID_MATERIAS"]][$c["ID_UNIDAD"]] = (float)$c["CALIFICACION"];
        }
        $stmt->close();

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

// ---------- Promedios ----------
$promMateria = [];
foreach ($materias as $m) {
    $valores = [];
    foreach ($unidades as $u) {
        $valores[] = $notas[$m["ID_MATERIAS"]][$u["ID_UNIDAD"]] ?? null;
    }
    $promMateria[$m["ID_MATERIAS"]] = promedio($valores);
}
$promGeneral = promedio(array_values($promMateria));

// ---------- Descarga en Excel ----------
if (isset($_GET["descargar"]) && $alumno !== null && count($materias) > 0) {
    $datos = [];
    $datos[] = ["Boleta de calificaciones"];
    $datos[] = ["Alumno: " . $alumno["MATRICULA"] . " - " . $alumno["NOMBRE_COMPLETO"]];
    $datos[] = ["Grupo: " . $textoGrupos . "   Fecha de emision: " . date("d/m/Y")];
    $datos[] = [];

    $encabezado = ["CLAVE", "MATERIA", "PROFESOR"];
    foreach ($unidades as $u) {
        $encabezado[] = "UNIDAD " . $u["ID_UNIDAD"];
    }
    $encabezado[] = "PROMEDIO";
    $datos[] = $encabezado;

    foreach ($materias as $m) {
        $fila = [$m["CLAVE_MATERIA"], $m["NOMBRE_MATERIA"], $m["PROFESOR"]];
        foreach ($unidades as $u) {
            $fila[] = $notas[$m["ID_MATERIAS"]][$u["ID_UNIDAD"]] ?? "";
        }
        $fila[] = $promMateria[$m["ID_MATERIAS"]] ?? "";
        $datos[] = $fila;
    }

    $pie = ["", "", "PROMEDIO GENERAL"];
    foreach ($unidades as $u) {
        $pie[] = "";
    }
    $pie[] = $promGeneral ?? "";
    $datos[] = $pie;

    enviarExcel($datos, "boleta_" . nombreArchivo($alumno["MATRICULA"]), 4, [0, 1, 2]);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $soloAlumno ? "Mis calificaciones - Boleta" : "Boleta de calificaciones" ?></title>
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
            --text-main: #ffffff;
            --text-muted: #e9d5ff;
        }

        body.dark-school-theme {
            background-color: var(--bg-main);
            color: #ffffff;
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
            color: #ffffff !important;
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
            color: #ffffff;
        }

        .form-body {
            padding: 1.75rem;
        }

        .form-label {
            color: #ffffff;
            font-size: 0.92rem;
        }

        .form-select,
        .form-select option {
            background-color: #ffffff;
            border: 1px solid var(--border-purple);
            color: #111827;
        }

        .form-select:focus {
            background-color: #ffffff;
            border-color: var(--accent-purple);
            color: #111827;
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

        .boleta-meta-box {
            --bs-table-bg: #172554;
            background-color: #172554 !important;
            border: 1px solid #3b82f6;
            border-radius: 10px;
            padding: 0.75rem 1rem;
        }

        .boleta-meta,
        .boleta-meta tr,
        .boleta-meta th,
        .boleta-meta td {
            --bs-table-bg: transparent;
            background-color: transparent !important;
            color: #ffffff !important;
        }

        .boleta-meta th {
            color: #bfdbfe !important;
            font-weight: 600;
        }

        .table-dark-purple {
            --bs-table-bg: var(--bg-card);
            --bs-table-striped-bg: #21113d;
            --bs-table-hover-bg: #29154b;
            --bs-table-border-color: var(--border-subtle);
            --bs-table-color: #ffffff;
            color: #ffffff;
            border-color: var(--border-subtle);
        }

        .table-dark-purple thead th {
            background-color: #2e1065;
            color: #ffffff;
            border-color: var(--border-purple);
            font-weight: 600;
        }

        .table-dark-purple tbody td,
        .table-dark-purple tbody th {
            color: #ffffff;
        }

        .table-dark-purple tfoot tr,
        .table-dark-purple tfoot th {
            background-color: #241247;
            color: #ffffff;
            border-color: var(--border-purple);
        }

        .firma {
            border-top: 1px solid var(--border-purple);
            padding-top: 6px;
            margin-top: 70px;
            color: #ffffff;
        }

        @media print {
            nav, .no-imprimir { display: none !important; }
            body.dark-school-theme { background: #ffffff !important; color: #000000 !important; }
            .form-shell { background: #ffffff !important; border: none !important; box-shadow: none !important; }
            .form-header { background: #ffffff !important; color: #000000 !important; border-bottom: 2px solid #000000 !important; }
            .form-header h2,
            .boleta-meta-box,
            .boleta-meta th,
            .boleta-meta td {
                background: #ffffff !important;
                border-color: transparent !important;
                color: #000000 !important;
            }
            .table-dark-purple,
            .table-dark-purple th,
            .table-dark-purple td,
            .table-dark-purple tfoot tr,
            .table-dark-purple tfoot th {
                background: #ffffff !important;
                color: #000000 !important;
                border-color: #000000 !important;
            }
            .firma { border-top-color: #000000 !important; color: #000000 !important; }
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
                    <h2><?= $soloAlumno ? "Mis calificaciones (Boleta)" : "Boleta de calificaciones" ?></h2>
                </div>

                <div class="form-body">

                    <?php if (!$soloAlumno) { ?>
                        <form method="GET" class="row g-3 align-items-end mb-4 no-imprimir">
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
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-blue w-100">Generar boleta</button>
                            </div>
                        </form>
                    <?php } ?>

                    <?php if ($idAlumno > 0 && $alumno === null) { ?>
                        <div class="alert alert-warning">No se encontró el alumno seleccionado.</div>
                    <?php } ?>

                    <?php if ($alumno !== null) { ?>
                        <?php if (!$soloAlumno) { ?><hr style="border-color: var(--border-subtle);" class="my-4 no-imprimir"><?php } ?>

                        <div class="row g-3 mb-3">
                            <div class="col-lg-7">
                                <div class="boleta-meta-box">
                                    <table class="table table-sm table-borderless boleta-meta w-auto mb-0">
                                        <tr><th class="pe-3">Alumno</th><td><?= h($alumno["NOMBRE_COMPLETO"]) ?></td></tr>
                                        <tr><th class="pe-3">Matrícula</th><td><?= h($alumno["MATRICULA"]) ?></td></tr>
                                        <tr><th class="pe-3">Grupo</th><td><?= h($textoGrupos) ?></td></tr>
                                        <tr><th class="pe-3">Fecha de emisión</th><td><?= date("d/m/Y") ?></td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-hover table-dark-purple align-middle text-center mb-0">
                                <thead>
                                    <tr>
                                        <th>Clave</th>
                                        <th class="text-start">Materia</th>
                                        <th class="text-start">Profesor</th>
                                        <?php foreach ($unidades as $u) { ?>
                                            <th title="<?= h($u["DESCRIPCION"]) ?>">U<?= (int)$u["ID_UNIDAD"] ?></th>
                                        <?php } ?>
                                        <th>Promedio</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($materias) === 0) { ?>
                                        <tr>
                                            <td colspan="<?= count($unidades) + 4 ?>" class="text-center py-3">
                                                Aún no tienes materias asignadas.
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    <?php foreach ($materias as $m) { ?>
                                        <tr>
                                            <td><?= h($m["CLAVE_MATERIA"]) ?></td>
                                            <td class="text-start"><?= h($m["NOMBRE_MATERIA"]) ?></td>
                                            <td class="text-start"><?= h($m["PROFESOR"]) ?></td>
                                            <?php foreach ($unidades as $u) { ?>
                                                <td><?= fmt($notas[$m["ID_MATERIAS"]][$u["ID_UNIDAD"]] ?? null) ?></td>
                                            <?php } ?>
                                            <th><?= fmt($promMateria[$m["ID_MATERIAS"]]) ?></th>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                                <?php if (count($materias) > 0) { ?>
                                    <tfoot>
                                        <tr>
                                            <th colspan="<?= count($unidades) + 3 ?>" class="text-end">Promedio general</th>
                                            <th><?= fmt($promGeneral) ?></th>
                                        </tr>
                                    </tfoot>
                                <?php } ?>
                            </table>
                        </div>

                        <?php if (count($unidades) > 0) { ?>
                            <p class="small mt-3 mb-0" style="color: #ffffff;">
                                <?php foreach ($unidades as $u) { ?>
                                    <span class="me-3">U<?= (int)$u["ID_UNIDAD"] ?> = <?= h($u["DESCRIPCION"]) ?></span>
                                <?php } ?>
                            </p>
                        <?php } ?>

                        <div class="row text-center mt-4">
                            <div class="col-6"><div class="firma">Control escolar</div></div>
                            <div class="col-6"><div class="firma">Firma del alumno</div></div>
                        </div>

                        <?php if (count($materias) > 0) { ?>
                            <div class="d-flex flex-wrap gap-2 no-imprimir mt-4">
                                <button type="button" class="btn btn-blue" onclick="window.print()">Imprimir boleta</button>
                                <a href="<?= $soloAlumno ? "?descargar=1" : "?id_alumno=" . (int)$idAlumno . "&descargar=1" ?>" class="btn btn-purple">Descargar Excel</a>
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