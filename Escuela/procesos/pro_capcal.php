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

function obtener_esquema($conexion, $tabla) {
    $cols = [];
    $pk = null;
    $res = mysqli_query($conexion, "SHOW COLUMNS FROM `{$tabla}`");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $cols[] = $row["Field"];
            if ($row["Key"] === "PRI" && $pk === null) {
                $pk = $row["Field"];
            }
        }
    }
    return ["cols" => $cols, "pk" => $pk];
}

$esq_capcal   = obtener_esquema($conexion, "capcal");
$esq_alumnos  = obtener_esquema($conexion, "alumnos");
$esq_materias = obtener_esquema($conexion, "materias");

$cols_cap = $esq_capcal["cols"];
$pk_cap   = $esq_capcal["pk"] ?? ($cols_cap[0] ?? "id");

// Detectar columnas reales en capcal
$col_alu_cap = null;
foreach (["id_alumno", "alumno_id", "matricula", "id_alu"] as $c) {
    if (in_array($c, $cols_cap, true)) {
        $col_alu_cap = $c;
        break;
    }
}

$col_mat_cap = null;
foreach (["id_mat", "id_materia", "materia_id", "clave_mat", "cve_mat"] as $c) {
    if (in_array($c, $cols_cap, true)) {
        $col_mat_cap = $c;
        break;
    }
}

$col_uni_cap = null;
foreach (["id_unidad", "unidad", "unidad_id", "num_unidad"] as $c) {
    if (in_array($c, $cols_cap, true)) {
        $col_uni_cap = $c;
        break;
    }
}

$col_cal_cap = null;
foreach (["calificacion", "calif", "nota", "promedio"] as $c) {
    if (in_array($c, $cols_cap, true)) {
        $col_cal_cap = $c;
        break;
    }
}

// Columnas clave en alumnos y materias
$pk_alu = $esq_alumnos["pk"] ?? "id_alumno";
foreach (["id_alumno", "id", "matricula"] as $c) {
    if (in_array($c, $esq_alumnos["cols"], true)) {
        $pk_alu = $c;
        break;
    }
}

$pk_mat = $esq_materias["pk"] ?? "id_mat";
foreach (["id_mat", "id_materia", "id", "clave"] as $c) {
    if (in_array($c, $esq_materias["cols"], true)) {
        $pk_mat = $c;
        break;
    }
}

// Función auxiliar para insertar o actualizar una calificación
function guardar_calificacion($conexion, $col_alu_cap, $col_mat_cap, $col_uni_cap, $col_cal_cap, $pk_cap, $val_alu, $val_mat, $val_uni, $val_cal) {
    if ($col_uni_cap !== null) {
        $stmt_chk = mysqli_prepare(
            $conexion,
            "SELECT `{$pk_cap}` FROM capcal WHERE `{$col_alu_cap}` = ? AND `{$col_mat_cap}` = ? AND `{$col_uni_cap}` = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt_chk, "sss", $val_alu, $val_mat, $val_uni);
    } else {
        $stmt_chk = mysqli_prepare(
            $conexion,
            "SELECT `{$pk_cap}` FROM capcal WHERE `{$col_alu_cap}` = ? AND `{$col_mat_cap}` = ? LIMIT 1"
        );
        mysqli_stmt_bind_param($stmt_chk, "ss", $val_alu, $val_mat);
    }

    mysqli_stmt_execute($stmt_chk);
    $res_chk = mysqli_stmt_get_result($stmt_chk);
    $existe = ($res_chk && mysqli_num_rows($res_chk) > 0);
    mysqli_stmt_close($stmt_chk);

    if ($existe) {
        if ($col_uni_cap !== null) {
            $stmt_upd = mysqli_prepare(
                $conexion,
                "UPDATE capcal SET `{$col_cal_cap}` = ? WHERE `{$col_alu_cap}` = ? AND `{$col_mat_cap}` = ? AND `{$col_uni_cap}` = ?"
            );
            mysqli_stmt_bind_param($stmt_upd, "dsss", $val_cal, $val_alu, $val_mat, $val_uni);
        } else {
            $stmt_upd = mysqli_prepare(
                $conexion,
                "UPDATE capcal SET `{$col_cal_cap}` = ? WHERE `{$col_alu_cap}` = ? AND `{$col_mat_cap}` = ?"
            );
            mysqli_stmt_bind_param($stmt_upd, "dss", $val_cal, $val_alu, $val_mat);
        }
        $ok = mysqli_stmt_execute($stmt_upd);
        mysqli_stmt_close($stmt_upd);
        return $ok ? "actualizada" : "error";
    } else {
        if ($col_uni_cap !== null) {
            $stmt_ins = mysqli_prepare(
                $conexion,
                "INSERT INTO capcal (`{$col_alu_cap}`, `{$col_mat_cap}`, `{$col_uni_cap}`, `{$col_cal_cap}`) VALUES (?, ?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_ins, "sssd", $val_alu, $val_mat, $val_uni, $val_cal);
        } else {
            $stmt_ins = mysqli_prepare(
                $conexion,
                "INSERT INTO capcal (`{$col_alu_cap}`, `{$col_mat_cap}`, `{$col_cal_cap}`) VALUES (?, ?, ?)"
            );
            mysqli_stmt_bind_param($stmt_ins, "ssd", $val_alu, $val_mat, $val_cal);
        }
        $ok = mysqli_stmt_execute($stmt_ins);
        mysqli_stmt_close($stmt_ins);
        return $ok ? "insertada" : "error";
    }
}

// Eliminar calificación por GET
if (isset($_GET["eliminar"])) {
    $id_elim = (int)$_GET["eliminar"];
    if ($id_elim > 0) {
        $stmt_del = mysqli_prepare($conexion, "DELETE FROM capcal WHERE `{$pk_cap}` = ?");
        mysqli_stmt_bind_param($stmt_del, "i", $id_elim);
        if (mysqli_stmt_execute($stmt_del)) {
            $mensaje     = "Calificación eliminada correctamente.";
            $tipo_alerta = "info";
        } else {
            $mensaje     = "No se pudo eliminar la calificación.";
            $tipo_alerta = "danger";
        }
        mysqli_stmt_close($stmt_del);
    }
}

// Procesar envío POST (individual o masivo)
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $modo = $_POST["modo"] ?? "individual";

    if ($col_alu_cap === null || $col_mat_cap === null || $col_cal_cap === null) {
        $mensaje     = "Revisa las columnas de la tabla capcal (alumno, materia y calificación).";
        $tipo_alerta = "danger";
    } elseif ($modo === "individual") {
        $val_alu = isset($_POST["id_alumno"]) ? trim($_POST["id_alumno"]) : "";
        $val_mat = isset($_POST["id_mat"]) ? trim($_POST["id_mat"]) : "";
        $val_uni = isset($_POST["unidad"]) ? trim($_POST["unidad"]) : "1";
        $cal_raw = isset($_POST["calificacion"]) ? str_replace(",", ".", trim($_POST["calificacion"])) : "";
        $val_cal = is_numeric($cal_raw) ? (float)$cal_raw : -1;

        if ($val_alu !== "" && $val_mat !== "" && $val_cal >= 0 && $val_cal <= 100) {
            $res_op = guardar_calificacion(
                $conexion, $col_alu_cap, $col_mat_cap, $col_uni_cap, $col_cal_cap, $pk_cap,
                $val_alu, $val_mat, $val_uni, $val_cal
            );
            if ($res_op === "insertada") {
                $mensaje     = "Calificación individual registrada correctamente.";
                $tipo_alerta = "success";
            } elseif ($res_op === "actualizada") {
                $mensaje     = "Calificación individual actualizada correctamente.";
                $tipo_alerta = "success";
            } else {
                $mensaje     = "Error al guardar la calificación: " . mysqli_error($conexion);
                $tipo_alerta = "danger";
            }
        } else {
            $mensaje     = "Selecciona alumno, materia y una calificación válida (0 a 100).";
            $tipo_alerta = "danger";
        }
    } elseif ($modo === "masiva") {
        $val_mat   = isset($_POST["id_mat_masiva"]) ? trim($_POST["id_mat_masiva"]) : "";
        $val_uni   = isset($_POST["unidad_masiva"]) ? trim($_POST["unidad_masiva"]) : "1";
        $califs_in = isset($_POST["calif_masiva"]) && is_array($_POST["calif_masiva"]) ? $_POST["calif_masiva"] : [];

        if ($val_mat === "") {
            $mensaje     = "Selecciona una materia para la captura masiva.";
            $tipo_alerta = "danger";
        } else {
            $guardadas = 0;
            $errores   = 0;

            foreach ($califs_in as $alu_key => $raw_val) {
                $alu_id = trim((string)$alu_key);
                $txt_val = str_replace(",", ".", trim((string)$raw_val));
                if ($txt_val === "") {
                    continue; // Omitir filas dejadas en blanco
                }
                if (!is_numeric($txt_val)) {
                    $errores++;
                    continue;
                }
                $num_cal = (float)$txt_val;
                if ($num_cal < 0 || $num_cal > 100) {
                    $errores++;
                    continue;
                }

                $op = guardar_calificacion(
                    $conexion, $col_alu_cap, $col_mat_cap, $col_uni_cap, $col_cal_cap, $pk_cap,
                    $alu_id, $val_mat, $val_uni, $num_cal
                );
                if ($op === "insertada" || $op === "actualizada") {
                    $guardadas++;
                } else {
                    $errores++;
                }
            }

            if ($guardadas > 0 && $errores === 0) {
                $mensaje     = "Captura masiva completada: {$guardadas} calificación(es) guardada(s).";
                $tipo_alerta = "success";
            } elseif ($guardadas > 0 && $errores > 0) {
                $mensaje     = "Se guardaron {$guardadas} calificación(es), pero {$errores} registro(s) tuvieron error o valor fuera de rango.";
                $tipo_alerta = "warning";
            } else {
                $mensaje     = "No se guardó ninguna calificación. Ingresa al menos un valor numérico entre 0 y 100.";
                $tipo_alerta = "warning";
            }
        }
    }
}

// Consultar alumnos activos
$order_alu = in_array("apaterno", $esq_alumnos["cols"], true) ? "ORDER BY apaterno, amaterno, nombre" : "";
$res_alumnos = mysqli_query($conexion, "SELECT * FROM alumnos WHERE estatus = 1 {$order_alu}");
$lista_alumnos = [];
if ($res_alumnos) {
    while ($a = mysqli_fetch_assoc($res_alumnos)) {
        $lista_alumnos[] = $a;
    }
}

// Consultar materias
$res_materias = mysqli_query($conexion, "SELECT * FROM materias");
$lista_materias = [];
if ($res_materias) {
    while ($m = mysqli_fetch_assoc($res_materias)) {
        $lista_materias[] = $m;
    }
}

// Consultar unidades si existe la tabla unidad
$res_unidades = mysqli_query($conexion, "SELECT * FROM unidad");
$lista_unidades = [];
if ($res_unidades) {
    while ($u = mysqli_fetch_assoc($res_unidades)) {
        $lista_unidades[] = $u;
    }
}

// Consultar calificaciones registradas
$expr_alu = $col_alu_cap ? "c.`{$col_alu_cap}`" : "''";
$expr_mat = $col_mat_cap ? "c.`{$col_mat_cap}`" : "''";
$expr_uni = $col_uni_cap ? "c.`{$col_uni_cap}`" : "'—'";
$expr_cal = $col_cal_cap ? "c.`{$col_cal_cap}`" : "0";

$joins = "";
$extra_select = "";

if ($col_alu_cap !== null && in_array($pk_alu, $esq_alumnos["cols"], true)) {
    $joins .= " LEFT JOIN alumnos a ON c.`{$col_alu_cap}` = a.`{$pk_alu}`";
    $extra_select .= ", a.apaterno, a.amaterno, a.nombre AS nombre_alumno";
}

if ($col_mat_cap !== null && in_array($pk_mat, $esq_materias["cols"], true)) {
    $joins .= " LEFT JOIN materias m ON c.`{$col_mat_cap}` = m.`{$pk_mat}`";
    $extra_select .= ", m.*";
}

$sql_capcal = "
    SELECT
        c.`{$pk_cap}` AS id_reg,
        {$expr_alu} AS cod_alu,
        {$expr_mat} AS cod_mat,
        {$expr_uni} AS cod_uni,
        {$expr_cal} AS calif
        {$extra_select}
    FROM capcal c
    {$joins}
    ORDER BY c.`{$pk_cap}` DESC
";
$calificaciones = mysqli_query($conexion, $sql_capcal);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos — Registro de Calificaciones (Individual y Masiva)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fff5f7; color: #333; }
        h2, h3 { color: #d63384; margin-top: 20px; }
        .card-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.06); max-width: 960px; }
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
                <li><a class="dropdown-item active" href="../procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_asignat.php">Asignat</a></li>
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

    <h2>Registro de Calificaciones (Individual y Masiva)</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_alerta, ENT_QUOTES, 'UTF-8') ?> mt-3" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <!-- Pestañas para alternar entre Individual y Masiva -->
    <ul class="nav nav-tabs mt-3" id="tabsCalif" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-individual" data-bs-toggle="tab" data-bs-target="#pane-individual" type="button" role="tab">
                Captura Individual
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-masiva" data-bs-toggle="tab" data-bs-target="#pane-masiva" type="button" role="tab">
                Captura Masiva
            </button>
        </li>
    </ul>

    <div class="tab-content card-box border border-top-0 rounded-bottom">
        <!-- CAPTURA INDIVIDUAL -->
        <div class="tab-pane fade show active" id="pane-individual" role="tabpanel">
            <form method="POST" action="pro_capcal.php" class="row g-3">
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
                                <option value="<?= htmlspecialchars($valAlu, ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($valAlu . ' - ' . $nomAlu, ENT_QUOTES, 'UTF-8') ?>
                                </option>
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
                                    <option value="<?= htmlspecialchars($valUni, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($nomUni, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="unidad" id="unidad" class="form-control" min="1" max="20" value="1" required />
                    <?php endif; ?>
                </div>

                <div class="col-md-2">
                    <label for="calificacion" class="form-label">Calificación</label>
                    <input type="number" step="0.1" min="0" max="100" name="calificacion" id="calificacion" class="form-control" required />
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Guardar individual</button>
                </div>
            </form>
        </div>

        <!-- CAPTURA MASIVA -->
        <div class="tab-pane fade" id="pane-masiva" role="tabpanel">
            <form method="POST" action="pro_capcal.php" class="row g-3">
                <input type="hidden" name="modo" value="masiva" />

                <div class="col-md-6">
                    <label for="id_mat_masiva" class="form-label">Materia</label>
                    <select name="id_mat_masiva" id="id_mat_masiva" class="form-select" required>
                        <option value="">Selecciona una materia...</option>
                        <?php foreach ($lista_materias as $m): ?>
                            <?php
                            $valMat = (string)($m[$pk_mat] ?? $m['id_mat'] ?? $m['id_materia'] ?? $m['id'] ?? '');
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
                                    <option value="<?= htmlspecialchars($valUni, ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars($nomUni, ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="unidad_masiva" id="unidad_masiva" class="form-control" min="1" max="20" value="1" required />
                    <?php endif; ?>
                </div>

                <div class="col-12">
                    <p class="text-muted small mb-2">Escribe la calificación (0 a 100) en los alumnos que desees registrar; las celdas vacías se omiten.</p>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID / Matrícula</th>
                                    <th>Alumno Activo</th>
                                    <th style="width: 170px;">Calificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($lista_alumnos) > 0): ?>
                                    <?php foreach ($lista_alumnos as $a): ?>
                                        <?php
                                        $valAlu = (string)($a[$pk_alu] ?? $a['id_alumno'] ?? $a['id'] ?? '');
                                        $nomAlu = trim(($a['apaterno'] ?? '') . ' ' . ($a['amaterno'] ?? '') . ', ' . ($a['nombre'] ?? ''));
                                        ?>
                                        <?php if ($valAlu !== ''): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($valAlu, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td><?= htmlspecialchars($nomAlu, ENT_QUOTES, 'UTF-8') ?></td>
                                                <td>
                                                    <input type="number"
                                                           step="0.1"
                                                           min="0"
                                                           max="100"
                                                           name="calif_masiva[<?= htmlspecialchars($valAlu, ENT_QUOTES, 'UTF-8') ?>]"
                                                           class="form-control form-control-sm"
                                                           placeholder="0 - 100" />
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center text-muted">No hay alumnos activos.</td>
                                    </tr>
                                <?php endif; ?>
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
    <table class="table table-bordered table-striped">
        <thead class="table-dark">
            <tr>
                <th>ID</th>
                <th>Alumno</th>
                <th>Materia</th>
                <th>Unidad</th>
                <th>Calificación</th>
                <th style="width: 90px; text-align: center;">Quitar</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($calificaciones && mysqli_num_rows($calificaciones) > 0): ?>
                <?php while ($row = mysqli_fetch_assoc($calificaciones)): ?>
                    <?php
                    $alumnoTexto = trim(($row['apaterno'] ?? '') . ' ' . ($row['amaterno'] ?? '') . ', ' . ($row['nombre_alumno'] ?? ''));
                    if ($alumnoTexto === ',') {
                        $alumnoTexto = $row['cod_alu'];
                    }
                    $materiaTexto = $row['nombre'] ?? $row['materia'] ?? $row['asignatura'] ?? $row['cod_mat'];
                    ?>
                    <tr>
                        <td><?= (int)$row['id_reg'] ?></td>
                        <td><?= htmlspecialchars((string)$row['cod_alu'] . ' — ' . $alumnoTexto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['cod_mat'] . ' — ' . $materiaTexto, ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$row['cod_uni'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong><?= htmlspecialchars((string)$row['calif'], ENT_QUOTES, 'UTF-8') ?></strong></td>
                        <td style="text-align: center;">
                            <a href="?eliminar=<?= (int)$row['id_reg'] ?>"
                               class="btn btn-sm btn-danger"
                               title="Eliminar calificación"
                               onclick="return confirm('¿Deseas eliminar esta calificación?')">
                                <i class="fa fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="text-center text-muted">No hay calificaciones registradas todavía.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>