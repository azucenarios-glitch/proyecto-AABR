<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

$cal_minima_aprobatoria = 6.0;
$mensaje     = "";
$tipo_alerta = "success";

function obtener_esquema_cap(PDO $pdo, string $tabla): array {
    $cols = [];
    $pk = null;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}`");
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cols[] = $row["Field"];
                if ($row["Key"] === "PRI" && $pk === null) {
                    $pk = $row["Field"];
                }
            }
        }
    } catch (PDOException $e) {
        return ["cols" => [], "pk" => null];
    }
    return ["cols" => $cols, "pk" => $pk];
}

$conexion->exec("
    CREATE TABLE IF NOT EXISTS capcal (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_alumno VARCHAR(50) NOT NULL,
        id_mat VARCHAR(50) NOT NULL,
        unidad VARCHAR(50) DEFAULT '1',
        calificacion DECIMAL(6,2) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$esq_capcal   = obtener_esquema_cap($conexion, "capcal");
$esq_alumnos  = obtener_esquema_cap($conexion, "alumnos");
$esq_materias = obtener_esquema_cap($conexion, "materias");

$cols_cap = $esq_capcal["cols"];
if (!in_array("id_alumno", $cols_cap, true) && !in_array("alumno_id", $cols_cap, true) && !in_array("matricula", $cols_cap, true)) {
    $conexion->exec("ALTER TABLE capcal ADD COLUMN id_alumno VARCHAR(50) NOT NULL DEFAULT ''");
}
if (!in_array("id_mat", $cols_cap, true) && !in_array("id_materia", $cols_cap, true) && !in_array("materia_id", $cols_cap, true)) {
    $conexion->exec("ALTER TABLE capcal ADD COLUMN id_mat VARCHAR(50) NOT NULL DEFAULT ''");
}
if (!in_array("unidad", $cols_cap, true) && !in_array("id_unidad", $cols_cap, true)) {
    $conexion->exec("ALTER TABLE capcal ADD COLUMN unidad VARCHAR(50) NOT NULL DEFAULT '1'");
}
if (!in_array("calificacion", $cols_cap, true) && !in_array("calif", $cols_cap, true) && !in_array("nota", $cols_cap, true)) {
    $conexion->exec("ALTER TABLE capcal ADD COLUMN calificacion DECIMAL(6,2) NOT NULL DEFAULT 0");
}

$esq_capcal = obtener_esquema_cap($conexion, "capcal");
$cols_cap   = $esq_capcal["cols"];
$pk_cap     = $esq_capcal["pk"] ?? ($cols_cap[0] ?? "id");

$col_alu_cap = null;
foreach (["id_alumno", "alumno_id", "matricula", "id_alu"] as $c) {
    if (in_array($c, $cols_cap, true)) { $col_alu_cap = $c; break; }
}
$col_mat_cap = null;
foreach (["id_mat", "id_materia", "materia_id", "clave_mat", "cve_mat"] as $c) {
    if (in_array($c, $cols_cap, true)) { $col_mat_cap = $c; break; }
}
$col_uni_cap = null;
foreach (["unidad", "id_unidad", "unidad_id", "num_unidad"] as $c) {
    if (in_array($c, $cols_cap, true)) { $col_uni_cap = $c; break; }
}
$col_cal_cap = null;
foreach (["calificacion", "calif", "nota", "promedio"] as $c) {
    if (in_array($c, $cols_cap, true)) { $col_cal_cap = $c; break; }
}

$pk_alu = $esq_alumnos["pk"] ?? "id_alumno";
foreach (["id_alumno", "id", "matricula"] as $c) {
    if (in_array($c, $esq_alumnos["cols"], true)) { $pk_alu = $c; break; }
}

$pk_mat = $esq_materias["pk"] ?? "id_mat";
foreach (["id_mat", "id_materia", "id", "clave"] as $c) {
    if (in_array($c, $esq_materias["cols"], true)) { $pk_mat = $c; break; }
}

function guardar_calificacion_pdo(PDO $pdo, string $col_alu, string $col_mat, ?string $col_uni, string $col_cal, string $pk, string $val_alu, string $val_mat, string $val_uni, float $val_cal): string {
    if ($col_uni !== null) {
        $chk = $pdo->prepare("SELECT `{$pk}` FROM capcal WHERE `{$col_alu}` = ? AND `{$col_mat}` = ? AND `{$col_uni}` = ? LIMIT 1");
        $chk->execute([$val_alu, $val_mat, $val_uni]);
    } else {
        $chk = $pdo->prepare("SELECT `{$pk}` FROM capcal WHERE `{$col_alu}` = ? AND `{$col_mat}` = ? LIMIT 1");
        $chk->execute([$val_alu, $val_mat]);
    }

    if ($chk->fetch()) {
        if ($col_uni !== null) {
            $upd = $pdo->prepare("UPDATE capcal SET `{$col_cal}` = ? WHERE `{$col_alu}` = ? AND `{$col_mat}` = ? AND `{$col_uni}` = ?");
            $ok = $upd->execute([$val_cal, $val_alu, $val_mat, $val_uni]);
        } else {
            $upd = $pdo->prepare("UPDATE capcal SET `{$col_cal}` = ? WHERE `{$col_alu}` = ? AND `{$col_mat}` = ?");
            $ok = $upd->execute([$val_cal, $val_alu, $val_mat]);
        }
        return $ok ? "actualizada" : "error";
    } else {
        if ($col_uni !== null) {
            $ins = $pdo->prepare("INSERT INTO capcal (`{$col_alu}`, `{$col_mat}`, `{$col_uni}`, `{$col_cal}`) VALUES (?, ?, ?, ?)");
            $ok = $ins->execute([$val_alu, $val_mat, $val_uni, $val_cal]);
        } else {
            $ins = $pdo->prepare("INSERT INTO capcal (`{$col_alu}`, `{$col_mat}`, `{$col_cal}`) VALUES (?, ?, ?)");
            $ok = $ins->execute([$val_alu, $val_mat, $val_cal]);
        }
        return $ok ? "insertada" : "error";
    }
}

if (isset($_GET["eliminar"])) {
    $id_elim = (int)$_GET["eliminar"];
    if ($id_elim > 0) {
        $del = $conexion->prepare("DELETE FROM capcal WHERE `{$pk_cap}` = ?");
        $del->execute([$id_elim]);
        $mensaje     = "Calificacion eliminada correctamente.";
        $tipo_alerta = "info";
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $modo = $_POST["modo"] ?? "individual";
    if ($modo === "individual") {
        $val_alu = trim((string)($_POST["id_alumno"] ?? ""));
        $val_mat = trim((string)($_POST["id_mat"] ?? ""));
        $val_uni = trim((string)($_POST["unidad"] ?? "1"));
        $cal_raw = str_replace(",", ".", trim((string)($_POST["calificacion"] ?? "")));
        $val_cal = is_numeric($cal_raw) ? (float)$cal_raw : -1;

        if ($val_alu !== "" && $val_mat !== "" && $val_cal >= 0 && $val_cal <= 100) {
            $op = guardar_calificacion_pdo($conexion, $col_alu_cap, $col_mat_cap, $col_uni_cap, $col_cal_cap, $pk_cap, $val_alu, $val_mat, $val_uni, $val_cal);
            $mensaje = ($op === "actualizada") ? "Calificacion individual actualizada correctamente." : "Calificacion individual registrada correctamente.";
            $tipo_alerta = "success";
        } else {
            $mensaje = "Selecciona alumno, materia y una calificacion valida (0 a 100).";
            $tipo_alerta = "danger";
        }
    } elseif ($modo === "masiva") {
        $val_mat   = trim((string)($_POST["id_mat_masiva"] ?? ""));
        $val_uni   = trim((string)($_POST["unidad_masiva"] ?? "1"));
        $califs_in = (isset($_POST["calif_masiva"]) && is_array($_POST["calif_masiva"])) ? $_POST["calif_masiva"] : [];

        if ($val_mat === "") {
            $mensaje = "Selecciona una materia para la captura masiva.";
            $tipo_alerta = "danger";
        } else {
            $guardadas = 0;
            $errores   = 0;
            foreach ($califs_in as $alu_key => $raw_val) {
                $alu_id  = trim((string)$alu_key);
                $txt_val = str_replace(",", ".", trim((string)$raw_val));
                if ($txt_val === "") continue;
                if (!is_numeric($txt_val)) { $errores++; continue; }
                $num_cal = (float)$txt_val;
                if ($num_cal < 0 || $num_cal > 100) { $errores++; continue; }
                $op = guardar_calificacion_pdo($conexion, $col_alu_cap, $col_mat_cap, $col_uni_cap, $col_cal_cap, $pk_cap, $alu_id, $val_mat, $val_uni, $num_cal);
                if ($op === "insertada" || $op === "actualizada") $guardadas++;
                else $errores++;
            }
            $mensaje = "Captura masiva: {$guardadas} guardada(s)" . ($errores > 0 ? ", {$errores} con error." : ".");
            $tipo_alerta = ($guardadas > 0) ? "success" : "warning";
        }
    }
}

$order_alu = in_array("apaterno", $esq_alumnos["cols"], true) ? "ORDER BY apaterno, amaterno, nombre" : "";
$lista_alumnos = $conexion->query("SELECT * FROM alumnos WHERE estatus = 1 {$order_alu}")->fetchAll(PDO::FETCH_ASSOC);
$lista_materias = $conexion->query("SELECT * FROM materias")->fetchAll(PDO::FETCH_ASSOC);

$lista_unidades = [];
try {
    $lista_unidades = $conexion->query("SELECT * FROM unidad")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $lista_unidades = [];
}

$expr_alu = $col_alu_cap ? "c.`{$col_alu_cap}`" : "''";
$expr_mat = $col_mat_cap ? "c.`{$col_mat_cap}`" : "''";
$expr_uni = $col_uni_cap ? "c.`{$col_uni_cap}`" : "'-'";
$expr_cal = $col_cal_cap ? "c.`{$col_cal_cap}`" : "0";

$joins = "";
$extra_select = "";
if ($col_alu_cap !== null && in_array($pk_alu, $esq_alumnos["cols"], true)) {
    $joins .= " LEFT JOIN alumnos a ON CAST(c.`{$col_alu_cap}` AS CHAR) = CAST(a.`{$pk_alu}` AS CHAR)";
    $extra_select .= ", a.apaterno, a.amaterno, a.nombre AS nombre_alumno";
}
if ($col_mat_cap !== null && in_array($pk_mat, $esq_materias["cols"], true)) {
    $joins .= " LEFT JOIN materias m ON CAST(c.`{$col_mat_cap}` AS CHAR) = CAST(m.`{$pk_mat}` AS CHAR)";
    $extra_select .= ", m.*";
}

$calificaciones = $conexion->query("
    SELECT c.`{$pk_cap}` AS id_reg, {$expr_alu} AS cod_alu, {$expr_mat} AS cod_mat, {$expr_uni} AS cod_uni, {$expr_cal} AS calif {$extra_select}
    FROM capcal c {$joins} ORDER BY c.`{$pk_cap}` DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos - Registro de Calificaciones (Individual y Masiva)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff; --rojo-baja: #dc2626; --rojo-fondo: #450a0a;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2, h3 { color: var(--morado-claro); margin-top: 20px; }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .nav-tabs { border-bottom-color: var(--borde-morado); }
        .nav-tabs .nav-link { color: var(--morado-claro); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 4px; }
        .nav-tabs .nav-link.active { color: var(--blanco); background-color: var(--morado-principal); border-color: var(--morado-claro); }
        .card-box { background: var(--negro-panel); color: var(--blanco); padding: 20px; border: 1px solid var(--borde-morado) !important; border-radius: 0 0 8px 8px; max-width: 960px; }
        .form-label, .text-muted { color: var(--morado-claro) !important; }
        .form-control, .form-select { background-color: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); }
        .btn-primary, .btn-success { background-color: var(--morado-principal); border-color: var(--blanco); color: var(--blanco); font-weight: bold; }
        .btn-primary:hover, .btn-success:hover { background-color: var(--morado-oscuro); border-color: var(--morado-claro); color: var(--blanco); }
        .btn-danger { background-color: var(--morado-oscuro); border-color: var(--morado-claro); color: var(--blanco); }
        .alert { background-color: var(--negro-panel); color: var(--blanco); border: 1px solid var(--morado-principal); max-width: 960px; }
        table.tabla-morada { border-collapse: collapse; width: 100%; background: var(--negro-panel); color: var(--blanco); margin-top: 15px; }
        table.tabla-morada th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; border: 1px solid var(--borde-morado); }
        table.tabla-morada td { border: 1px solid var(--borde-morado); padding: 10px; color: var(--blanco); vertical-align: middle; }
        table.tabla-morada tr:nth-child(even) { background: var(--negro-fila-alt); }
        .calif-baja { background-color: var(--rojo-fondo); color: #fca5a5 !important; border: 1px solid var(--rojo-baja); padding: 3px 8px; border-radius: 4px; display: inline-block; }
    </style>
</head>
<body class="p-3 m-0">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link" href="/proyecto-AABR/Escuela/index.php">Inicio</a></li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Baja</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_profesores.php">Profesor</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button">Procesos</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_materias.php">Materias</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_asignat.php">Asignat</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_insgrup.php">Insgrup</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_vista.php">Vista Alumno</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Registro</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_profesores.php">Profesor</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_alumno.php">Alumno</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_unidad.php">Unidad</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_usuarios.php">Usuarios</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Reporte</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_general.php">General</a></li>
            </ul>
        </li>
    </ul>

    <h2>Registro de Calificaciones (Individual y Masiva)</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_alerta, ENT_QUOTES, 'UTF-8') ?> mt-3" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mt-3" id="tabsCalif" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-individual" data-bs-toggle="tab" data-bs-target="#pane-individual" type="button" role="tab">Captura Individual</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-masiva" data-bs-toggle="tab" data-bs-target="#pane-masiva" type="button" role="tab">Captura Masiva</button>
        </li>
    </ul>

    <div class="tab-content card-box rounded-bottom">
        <div class="tab-pane fade show active" id="pane-individual" role="tabpanel">
            <form method="POST" action="/proyecto-AABR/Escuela/procesos/pro_capcal.php" class="row g-3">
                <input type="hidden" name="modo" value="individual" />
                <div class="col-md-4">
                    <label for="id_alumno" class="form-label">Alumno activo</label>
                    <select name="id_alumno" id="id_alumno" class="form-select" required>
                        <option value="">Selecciona un alumno...</option>
                        <?php foreach ($lista_alumnos as $a): ?>
                            <?php
                            $valAlu = (string)($a[$pk_alu] ?? $a['id_alumno'] ?? $a['id'] ?? '');
                            $nomAlu = trim(($a['apaterno'] ?? '') . ' ' . ($a['amaterno'] ?? '') . ', ' . ($a['nombre'] ?? ''));
                            ?>
                            <?php if ($valAlu !== ''): ?>
                                <option value="<?= htmlspecialchars($valAlu, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($valAlu . ' - ' . $nomAlu, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="id_mat" class="form-label">Materia</label>
                    <select name="id_mat" id="id_mat" class="form-select" required>
                        <option value="">Selecciona una materia...</option>
                        <?php foreach ($lista_materias as $m): ?>
                            <?php
                            $valMat = (string)($m[$pk_mat] ?? $m['id_mat'] ?? $m['id_materia'] ?? $m['id'] ?? '');
                            $nomMat = $m['nombre'] ?? $m['materia'] ?? $m['asignatura'] ?? $m['descripcion'] ?? $valMat;
                            ?>
                            <?php if ($valMat !== ''): ?>
                                <option value="<?= htmlspecialchars($valMat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($valMat . ' - ' . $nomMat, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="unidad" class="form-label">Unidad</label>
                    <?php if (count($lista_unidades) > 0): ?>
                        <select name="unidad" id="unidad" class="form-select" required>
                            <?php foreach ($lista_unidades as $u): ?>
                                <?php
                                $valUni = (string)($u['id_unidad'] ?? $u['unidad'] ?? $u['id'] ?? '');
                                $nomUni = $u['nombre'] ?? $u['descripcion'] ?? ("Unidad " . $valUni);
                                ?>
                                <?php if ($valUni !== ''): ?>
                                    <option value="<?= htmlspecialchars($valUni, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($nomUni, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="unidad" id="unidad" class="form-control" min="1" max="20" value="1" required />
                    <?php endif; ?>
                </div>
                <div class="col-md-2">
                    <label for="calificacion" class="form-label">Calificacion</label>
                    <input type="number" step="0.1" min="0" max="100" name="calificacion" id="calificacion" class="form-control" required />
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar individual</button>
                </div>
            </form>
        </div>

        <div class="tab-pane fade" id="pane-masiva" role="tabpanel">
            <form method="POST" action="/proyecto-AABR/Escuela/procesos/pro_capcal.php" class="row g-3">
                <input type="hidden" name="modo" value="masiva" />
                <div class="col-md-6">
                    <label for="id_mat_masiva" class="form-label">Materia</label>
                    <select name="id_mat_masiva" id="id_mat_masiva" class="form-select" required>
                        <option value="">Selecciona una materia...</option>
                        <?php foreach ($lista_materias as $m): ?>
                            <?php
                            $valMat = (string)($m[$pk_mat] ?? $m['id_mat'] ?? $m['id_materia'] ?? $m['id'] ?? '');
                            $nomMat = $m['nombre'] ?? $m['materia'] ?? $m['asignatura'] ?? $m['descripcion'] ?? $valMat;
                            ?>
                            <?php if ($valMat !== ''): ?>
                                <option value="<?= htmlspecialchars($valMat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($valMat . ' - ' . $nomMat, ENT_QUOTES, 'UTF-8') ?></option>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="unidad_masiva" class="form-label">Unidad</label>
                    <?php if (count($lista_unidades) > 0): ?>
                        <select name="unidad_masiva" id="unidad_masiva" class="form-select" required>
                            <?php foreach ($lista_unidades as $u): ?>
                                <?php
                                $valUni = (string)($u['id_unidad'] ?? $u['unidad'] ?? $u['id'] ?? '');
                                $nomUni = $u['nombre'] ?? $u['descripcion'] ?? ("Unidad " . $valUni);
                                ?>
                                <?php if ($valUni !== ''): ?>
                                    <option value="<?= htmlspecialchars($valUni, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($nomUni, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="unidad_masiva" id="unidad_masiva" class="form-control" min="1" max="20" value="1" required />
                    <?php endif; ?>
                </div>
                <div class="col-12">
                    <p class="small mb-2">Escribe la calificacion (0 a 100) en los alumnos que desees registrar; las celdas vacias se omiten.</p>
                    <div class="table-responsive">
                        <table class="tabla-morada">
                            <thead>
                                <tr>
                                    <th>ID / Matricula</th>
                                    <th>Alumno Activo</th>
                                    <th style="width: 170px;">Calificacion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lista_alumnos as $a): ?>
                                    <?php
                                    $valAlu = (string)($a[$pk_alu] ?? $a['id_alumno'] ?? $a['id'] ?? '');
                                    $nomAlu = trim(($a['apaterno'] ?? '') . ' ' . ($a['amaterno'] ?? '') . ', ' . ($a['nombre'] ?? ''));
                                    ?>
                                    <?php if ($valAlu !== ''): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($valAlu, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($nomAlu, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><input type="number" step="0.1" min="0" max="100" name="calif_masiva[<?= htmlspecialchars($valAlu, ENT_QUOTES, 'UTF-8') ?>]" class="form-control form-control-sm" placeholder="0 - 100" /></td>
                                        </tr>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-success">Guardar captura masiva</button>
                </div>
            </form>
        </div>
    </div>

    <h3>Calificaciones Capturadas</h3>
    <table class="tabla-morada">
        <thead>
            <tr>
                <th>ID</th>
                <th>Alumno</th>
                <th>Materia</th>
                <th>Unidad</th>
                <th>Calificacion</th>
                <th style="width: 90px; text-align: center;">Quitar</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($calificaciones) > 0): ?>
                <?php foreach ($calificaciones as $row): ?>
                    <?php
                    $alumnoTexto = trim(($row['apaterno'] ?? '') . ' ' . ($row['amaterno'] ?? '') . ', ' . ($row['nombre_alumno'] ?? ''));
                    if ($alumnoTexto === ',') $alumnoTexto = $row['cod_alu'];
                    $materiaTexto = $row['nombre'] ?? $row['materia'] ?? $row['asignatura'] ?? $row['descripcion'] ?? $row['cod_mat'];
                    $calNum = is_numeric($row['calif']) ? (float)$row['calif'] : 0.0;
                    $esMenor = is_numeric($row['calif']) && ($calNum < $cal_minima_aprobatoria);
                    ?>
                    <tr>
                        <td><?= (int)$row['id_reg'] ?></td>
                        <td><?= htmlspecialchars((string)$row['cod_alu'] . ' - ' . $alumnoTexto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['cod_mat'] . ' - ' . $materiaTexto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['cod_uni'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <strong class="<?= $esMenor ? 'calif-baja' : '' ?>">
                                <?= is_numeric($row['calif']) ? number_format($calNum, 1) : htmlspecialchars((string)$row['calif'], ENT_QUOTES, 'UTF-8') ?>
                            </strong>
                        </td>
                        <td style="text-align: center;">
                            <a href="?eliminar=<?= (int)$row['id_reg'] ?>" class="btn btn-sm btn-danger" title="Eliminar calificacion" onclick="return confirm('Deseas eliminar esta calificacion?')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center">No hay calificaciones registradas todavia.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>