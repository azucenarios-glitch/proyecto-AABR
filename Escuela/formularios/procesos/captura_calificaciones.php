<?php
require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR', 'MAESTRO']);

function h($t) { return htmlspecialchars((string)$t, ENT_QUOTES, "UTF-8"); }

$modo  = (isset($_GET["modo"]) && $_GET["modo"] === "masiva") ? "masiva" : "individual";
$exito = "";
$error = "";
$soloMaestro = esMaestro();
$idProfesorSesion = $soloMaestro ? (int)$_SESSION["id_profesor"] : 0;

// ---------- Guardado (individual y masivo usan el mismo arreglo cal[ID_ALUMNO]) ----------
if (isset($_POST["guardar"])) {
    $idUnidadPost  = (int)$_POST["id_unidad"];
    $idMateriaPost = (int)$_POST["id_materia"];
    $guardadas = 0;
    $invalidas = 0;

    try {
        if ($soloMaestro) {
            $sql = "INSERT IGNORE INTO calificaciones (ID_ALUMNOS, ID_MATERIAS, CALIFICACION, ID_UNIDAD)
                    SELECT ?, ?, ?, ? FROM asignatura WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ? AND ID_PROFESORES = ? LIMIT 1";
            $stmt = $conexion->prepare($sql);
        } else {
            $sql = "INSERT IGNORE INTO calificaciones (ID_ALUMNOS, ID_MATERIAS, CALIFICACION, ID_UNIDAD)
                    SELECT ?, ?, ?, ? FROM asignatura WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ? LIMIT 1";
            $stmt = $conexion->prepare($sql);
        }

        foreach ($_POST["cal"] ?? [] as $idAlumnoPost => $valor) {
            $valor = trim((string)$valor);
            if ($valor === "") {
                continue;
            }
            if (!is_numeric($valor) || $valor < 0 || $valor > 10) {
                $invalidas++;
                continue;
            }
            $idAlumnoPost = (int)$idAlumnoPost;
            $calif = (float)$valor;
            if ($soloMaestro) {
                $stmt->bind_param("iidiiii", $idAlumnoPost, $idMateriaPost, $calif, $idUnidadPost, $idAlumnoPost, $idMateriaPost, $idProfesorSesion);
            } else {
                $stmt->bind_param("iidiii", $idAlumnoPost, $idMateriaPost, $calif, $idUnidadPost, $idAlumnoPost, $idMateriaPost);
            }
            $stmt->execute();
            $guardadas += $stmt->affected_rows;
        }
        $stmt->close();

        if ($guardadas > 0) {
            $exito = "Calificaciones guardadas: " . $guardadas . ".";
        }
        if ($invalidas > 0) {
            $error = "Hubo " . $invalidas . " calificación(es) fuera del rango 0 a 10 que no se guardaron.";
        }
        if ($guardadas === 0 && $invalidas === 0) {
            $error = "No se capturó ninguna calificación.";
        }
    } catch (mysqli_sql_exception $e) {
        $error = "Ocurrió un error al guardar: " . $e->getMessage();
    }
}

// ---------- Corrección de una calificación ya capturada ----------
$correccionOk = false;
if (isset($_POST["corregir_guardar"])) {
    $idUnidadPost  = (int)$_POST["id_unidad"];
    $idMateriaPost = (int)$_POST["id_materia"];
    $idAlumnoPost  = (int)$_POST["id_alumno"];
    $valor = trim((string)($_POST["nueva_cal"] ?? ""));

    if ($valor === "" || !is_numeric($valor) || $valor < 0 || $valor > 10) {
        $error = "La calificación debe ser un número entre 0 y 10.";
    } else {
        $permitido = true;
        if ($soloMaestro) {
            $chk = $conexion->prepare("SELECT 1 FROM asignatura WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ? AND ID_PROFESORES = ? LIMIT 1");
            $chk->bind_param("iii", $idAlumnoPost, $idMateriaPost, $idProfesorSesion);
            $chk->execute();
            $permitido = $chk->get_result()->fetch_assoc() !== null;
            $chk->close();
        }

        if (!$permitido) {
            $error = "No tienes permiso para modificar la calificación de este alumno o materia.";
        } else {
            try {
                $calif = (float)$valor;
                $stmt = $conexion->prepare("UPDATE calificaciones SET CALIFICACION = ? WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ? AND ID_UNIDAD = ?");
                $stmt->bind_param("diii", $calif, $idAlumnoPost, $idMateriaPost, $idUnidadPost);
                $stmt->execute();
                $stmt->close();
                $exito = "Calificación corregida correctamente.";
                $correccionOk = true;
            } catch (mysqli_sql_exception $e) {
                $error = "Ocurrió un error al corregir: " . $e->getMessage();
            }
        }
    }
}

// ---------- Datos para mostrar ----------
$corregir  = isset($_GET["corregir"]) && !$correccionOk;
$idUnidad  = (int)($_GET["id_unidad"] ?? 0);
$idMateria = (int)($_GET["id_materia"] ?? 0);
$idAlumno  = (int)($_GET["id_alumno"] ?? 0);
$idGrupo   = (int)($_GET["id_grupo"] ?? 0);

$unidades = $conexion->query("SELECT ID_UNIDAD, DESCRIPCION FROM unidad WHERE ESTATUS = 'ALTA' ORDER BY ID_UNIDAD")->fetch_all(MYSQLI_ASSOC);

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
} else {
    $materias = $conexion->query("SELECT ID_MATERIAS, CLAVE_MATERIA, NOMBRE_MATERIA FROM materias WHERE ESTATUS = 'ALTA' ORDER BY NOMBRE_MATERIA")->fetch_all(MYSQLI_ASSOC);
}

$alumnos = [];
$grupos = [];
$asignada = false;
$calActual = null;
$filas = [];

if ($modo === "individual") {
    if ($soloMaestro) {
        $stmt = $conexion->prepare(
            "SELECT DISTINCT a.ID_ALUMNOS, a.MATRICULA, a.APELLIDO_PATERNO, a.NOMBRE, CONCAT(a.NOMBRE, ' ', a.APELLIDO_PATERNO, ' ', a.APELLIDO_MATERNO) AS NOMBRE_COMPLETO
             FROM alumnos a
             INNER JOIN asignatura s ON s.ID_ALUMNOS = a.ID_ALUMNOS
             WHERE a.ESTATUS = 'ALTA' AND s.ID_PROFESORES = ?
             ORDER BY a.APELLIDO_PATERNO, a.NOMBRE"
        );
        $stmt->bind_param("i", $idProfesorSesion);
        $stmt->execute();
        $alumnos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    } else {
        $alumnos = $conexion->query("SELECT ID_ALUMNOS, MATRICULA, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM alumnos WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);
    }

    if ($idAlumno > 0 && $idMateria > 0 && $idUnidad > 0) {
        if ($soloMaestro) {
            $stmt = $conexion->prepare("SELECT ID_ASIGNATURA FROM asignatura WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ? AND ID_PROFESORES = ?");
            $stmt->bind_param("iii", $idAlumno, $idMateria, $idProfesorSesion);
        } else {
            $stmt = $conexion->prepare("SELECT ID_ASIGNATURA FROM asignatura WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ?");
            $stmt->bind_param("ii", $idAlumno, $idMateria);
        }
        $stmt->execute();
        $asignada = $stmt->get_result()->fetch_assoc() !== null;
        $stmt->close();

        if ($asignada) {
            $stmt = $conexion->prepare("SELECT CALIFICACION FROM calificaciones WHERE ID_ALUMNOS = ? AND ID_MATERIAS = ? AND ID_UNIDAD = ?");
            $stmt->bind_param("iii", $idAlumno, $idMateria, $idUnidad);
            $stmt->execute();
            $r = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            $calActual = $r ? $r["CALIFICACION"] : null;
        }
    }
} else {
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

    if ($idMateria > 0 && $idUnidad > 0) {
        $joinGrupo = "";
        $tipos = "";
        $params = [];
        if ($idGrupo > 0) {
            $joinGrupo = "INNER JOIN grupo_alumno ga ON ga.ID_ALUMNOS = a.ID_ALUMNOS AND ga.ID_GRUPOS = ? AND ga.ESTATUS = 'ALTA'";
            $tipos .= "i";
            $params[] = $idGrupo;
        }
        $tipos .= "ii";
        $params[] = $idUnidad;
        $params[] = $idMateria;

        $filtroProfesor = "";
        if ($soloMaestro) {
            $filtroProfesor = " AND s.ID_PROFESORES = ?";
            $tipos .= "i";
            $params[] = $idProfesorSesion;
        }

        $sql = "SELECT a.ID_ALUMNOS, a.MATRICULA,
                       CONCAT(a.NOMBRE, ' ', a.APELLIDO_PATERNO, ' ', a.APELLIDO_MATERNO) AS NOMBRE_COMPLETO,
                       c.CALIFICACION
                FROM asignatura s
                INNER JOIN alumnos a ON a.ID_ALUMNOS = s.ID_ALUMNOS AND a.ESTATUS = 'ALTA'
                $joinGrupo
                LEFT JOIN calificaciones c ON c.ID_ALUMNOS = a.ID_ALUMNOS AND c.ID_MATERIAS = s.ID_MATERIAS AND c.ID_UNIDAD = ?
                WHERE s.ID_MATERIAS = ? $filtroProfesor
                ORDER BY a.APELLIDO_PATERNO, a.NOMBRE";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param($tipos, ...$params);
        $stmt->execute();
        $filas = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Captura de calificaciones</title>
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

        .nav-tabs-purple {
            border-bottom-color: var(--border-subtle);
        }

        .nav-tabs-purple .nav-link {
            color: var(--text-muted) !important;
            border: 1px solid transparent;
        }

        .nav-tabs-purple .nav-link:hover {
            color: #ffffff !important;
            border-color: var(--border-subtle);
            background-color: var(--bg-card);
        }

        .nav-tabs-purple .nav-link.active {
            background-color: var(--accent-purple) !important;
            border-color: var(--accent-purple) !important;
            color: #ffffff !important;
            font-weight: 600;
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

        .link-purple {
            color: #c4b5fd;
            text-decoration: none;
        }

        .link-purple:hover {
            color: #ffffff;
            text-decoration: underline;
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
                    <h2>Captura de calificaciones</h2>
                </div>

                <div class="form-body">

                    <ul class="nav nav-tabs nav-tabs-purple mb-4">
                        <li class="nav-item">
                            <a class="nav-link <?= $modo === 'individual' ? 'active' : '' ?>" href="?modo=individual">Individual</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= $modo === 'masiva' ? 'active' : '' ?>" href="?modo=masiva">Masiva</a>
                        </li>
                    </ul>

                    <?php if ($exito !== "") { ?>
                        <div class="alert alert-success"><?= h($exito) ?></div>
                    <?php } ?>
                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= h($error) ?></div>
                    <?php } ?>

                    <?php if (count($unidades) === 0) { ?>
                        <div class="alert alert-warning">Primero registra al menos una unidad en Registros &gt; Unidades.</div>
                    <?php } ?>

                    <?php if ($soloMaestro && count($materias) === 0) { ?>
                        <div class="alert alert-info">Aún no tienes materias con alumnos asignados.</div>
                    <?php } ?>

                    <?php if ($modo === "individual") { ?>

                        <form method="GET" class="row g-3 align-items-end mb-4">
                            <input type="hidden" name="modo" value="individual">
                            <div class="col-sm-6 col-lg-4">
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
                            <div class="col-sm-6 col-lg-3">
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
                            <div class="col-sm-6 col-lg-3">
                                <label class="form-label">Unidad</label>
                                <select name="id_unidad" class="form-select" required>
                                    <option value="">Selecciona una unidad...</option>
                                    <?php foreach ($unidades as $u) { ?>
                                        <option value="<?= (int)$u["ID_UNIDAD"] ?>" <?= (int)$idUnidad === (int)$u["ID_UNIDAD"] ? "selected" : "" ?>>
                                            <?= h($u["ID_UNIDAD"] . " - " . $u["DESCRIPCION"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <button type="submit" class="btn btn-blue w-100">Seleccionar</button>
                            </div>
                        </form>

                        <?php if ($idAlumno > 0 && $idMateria > 0 && $idUnidad > 0 && !$asignada) { ?>
                            <div class="alert alert-warning">
                                <?= $soloMaestro
                                    ? "Ese alumno no tiene asignada esa materia contigo."
                                    : "Ese alumno no tiene asignada esa materia. Asígnala primero en Procesos > Asignación de materias." ?>
                            </div>
                        <?php } ?>

                        <?php if ($asignada) { ?>
                            <hr style="border-color: var(--border-subtle);" class="my-4">
                            <form method="POST">
                                <input type="hidden" name="id_unidad" value="<?= (int)$idUnidad ?>">
                                <input type="hidden" name="id_materia" value="<?= (int)$idMateria ?>">
                                <input type="hidden" name="id_alumno" value="<?= (int)$idAlumno ?>">

                                <div class="row g-3 align-items-end mb-3">
                                    <?php if ($calActual === null) { ?>
                                        <div class="col-sm-6 col-md-4">
                                            <label class="form-label">Calificación (capturar)</label>
                                            <input type="number" name="cal[<?= (int)$idAlumno ?>]" class="form-control" min="0" max="10" step="0.1" required>
                                        </div>
                                    <?php } ?>

                                    <div class="col-sm-6 col-md-4">
                                        <label class="form-label">Calificación registrada</label>
                                        <input type="text" class="form-control" value="<?= h($calActual) ?>" readonly>
                                    </div>

                                    <?php if ($calActual !== null && $corregir) { ?>
                                        <div class="col-sm-6 col-md-4">
                                            <label class="form-label">Nueva calificación</label>
                                            <input type="number" name="nueva_cal" class="form-control" min="0" max="10" step="0.1" value="<?= h($calActual) ?>" required>
                                        </div>
                                    <?php } ?>
                                </div>

                                <?php $urlBase = "?modo=individual&id_alumno=" . (int)$idAlumno . "&id_materia=" . (int)$idMateria . "&id_unidad=" . (int)$idUnidad; ?>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php if ($calActual === null) { ?>
                                        <button type="submit" name="guardar" class="btn btn-purple">Guardar calificación</button>
                                    <?php } elseif ($corregir) { ?>
                                        <button type="submit" name="corregir_guardar" class="btn btn-purple">Guardar corrección</button>
                                        <a href="<?= h($urlBase) ?>" class="btn btn-purple-outline">Cancelar</a>
                                    <?php } else { ?>
                                        <a href="<?= h($urlBase) ?>&corregir=1" class="btn btn-blue">Corregir calificación</a>
                                    <?php } ?>
                                </div>
                            </form>
                        <?php } ?>

                    <?php } else { ?>

                        <form method="GET" class="row g-3 align-items-end mb-4">
                            <input type="hidden" name="modo" value="masiva">
                            <div class="col-sm-6 col-lg-4">
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
                            <div class="col-sm-6 col-lg-3">
                                <label class="form-label">Unidad</label>
                                <select name="id_unidad" class="form-select" required>
                                    <option value="">Selecciona una unidad...</option>
                                    <?php foreach ($unidades as $u) { ?>
                                        <option value="<?= (int)$u["ID_UNIDAD"] ?>" <?= (int)$idUnidad === (int)$u["ID_UNIDAD"] ? "selected" : "" ?>>
                                            <?= h($u["ID_UNIDAD"] . " - " . $u["DESCRIPCION"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-3">
                                <label class="form-label">Grupo (opcional)</label>
                                <select name="id_grupo" class="form-select">
                                    <option value="0">Todos mis grupos</option>
                                    <?php foreach ($grupos as $g) { ?>
                                        <option value="<?= (int)$g["ID_GRUPOS"] ?>" <?= (int)$idGrupo === (int)$g["ID_GRUPOS"] ? "selected" : "" ?>>
                                            <?= h($g["NOMBRE_GRUPO"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="col-sm-6 col-lg-2">
                                <button type="submit" class="btn btn-blue w-100">Cargar alumnos</button>
                            </div>
                        </form>

                        <?php if ($idMateria > 0 && $idUnidad > 0) { ?>
                            <hr style="border-color: var(--border-subtle);" class="my-4">
                            <form method="POST">
                                <input type="hidden" name="id_unidad" value="<?= (int)$idUnidad ?>">
                                <input type="hidden" name="id_materia" value="<?= (int)$idMateria ?>">

                                <div class="table-responsive mb-4">
                                    <table class="table table-bordered table-striped table-hover table-dark-purple align-middle mb-0">
                                        <thead>
                                            <tr>
                                                <th>Id alumno</th>
                                                <th>Matrícula</th>
                                                <th>Alumno</th>
                                                <th style="width:180px">Calificación (capturar)</th>
                                                <th style="width:180px">Calificación registrada</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($filas) === 0) { ?>
                                                <tr>
                                                    <td colspan="5" class="text-center py-3" style="color: var(--text-muted);">
                                                        No hay alumnos activos con esa materia asignada<?= $idGrupo > 0 ? " en ese grupo" : "" ?>.
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                            <?php foreach ($filas as $f) { ?>
                                                <tr>
                                                    <td><?= (int)$f["ID_ALUMNOS"] ?></td>
                                                    <td><?= h($f["MATRICULA"]) ?></td>
                                                    <td><?= h($f["NOMBRE_COMPLETO"]) ?></td>
                                                    <td>
                                                        <?php if ($f["CALIFICACION"] === null) { ?>
                                                            <input type="number" name="cal[<?= (int)$f["ID_ALUMNOS"] ?>]" class="form-control" min="0" max="10" step="0.1">
                                                        <?php } ?>
                                                    </td>
                                                    <td>
                                                        <input type="text" class="form-control" value="<?= h($f["CALIFICACION"]) ?>" readonly>
                                                        <?php if ($f["CALIFICACION"] !== null) { ?>
                                                            <a class="small link-purple d-inline-block mt-1" href="?modo=individual&id_alumno=<?= (int)$f["ID_ALUMNOS"] ?>&id_materia=<?= (int)$idMateria ?>&id_unidad=<?= (int)$idUnidad ?>&corregir=1">Corregir</a>
                                                        <?php } ?>
                                                    </td>
                                                </tr>
                                            <?php } ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex flex-wrap gap-2">
                                    <button type="submit" name="guardar" class="btn btn-purple">Guardar calificaciones</button>
                                    <a href="<?= $base ?>/formularios/reportes/rep_calificaciones.php?id_materia=<?= (int)$idMateria ?>&id_grupo=<?= (int)$idGrupo ?>" class="btn btn-blue-outline">Enviar a Reportes</a>
                                    <?php if ($idGrupo > 0) { ?>
                                        <a href="<?= $base ?>/formularios/reportes/rep_calificaciones.php?id_materia=<?= (int)$idMateria ?>&id_grupo=<?= (int)$idGrupo ?>&descargar=1" class="btn btn-blue">Descargar Excel</a>
                                    <?php } ?>
                                </div>

                                <div class="small mt-3" style="color: var(--text-muted);">
                                    Solo se guardan las filas que tengan calificación capturada. Guarda antes de enviar al reporte: el Excel incluye las calificaciones ya guardadas.
                                    <?php if ($idGrupo === 0) { ?>Para descargar el Excel elige un grupo; el reporte es por materia y grupo.<?php } ?>
                                </div>
                            </form>
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