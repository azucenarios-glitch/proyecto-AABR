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

function obtener_esquema_rep($conexion, $tabla) {
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

function primera_coincidencia($cols, $candidatos, $default = null) {
    foreach ($candidatos as $c) {
        if (in_array($c, $cols, true)) {
            return $c;
        }
    }
    return $default;
}

$esq_alumnos  = obtener_esquema_rep($conexion, "alumnos");
$esq_grupos   = obtener_esquema_rep($conexion, "grupos");
$esq_materias = obtener_esquema_rep($conexion, "materias");
$esq_asigmat  = obtener_esquema_rep($conexion, "asigmat");
$esq_capcal   = obtener_esquema_rep($conexion, "capcal");

$pk_alu = primera_coincidencia($esq_alumnos["cols"], ["id_alumno", "id", "matricula"], "id_alumno");
$col_grp_alu = primera_coincidencia($esq_alumnos["cols"], ["id_grupo", "grupo", "id_grp"], "id_grupo");
$tiene_estatus_alu = in_array("estatus", $esq_alumnos["cols"], true);

$pk_grp = primera_coincidencia($esq_grupos["cols"], ["id_grupo", "id", "clave"], "id_grupo");
$col_nom_grp = primera_coincidencia($esq_grupos["cols"], ["nombre", "grupo", "clave", "descripcion"], $pk_grp);

$pk_mat = primera_coincidencia($esq_materias["cols"], ["id_mat", "id_materia", "id", "clave"], "id_mat");
$col_nom_mat = primera_coincidencia($esq_materias["cols"], ["nombre", "materia", "asignatura", "descripcion"], $pk_mat);

$col_alu_asg = primera_coincidencia($esq_asigmat["cols"], ["id_alumno", "alumno_id", "matricula"], "id_alumno");
$col_mat_asg = primera_coincidencia($esq_asigmat["cols"], ["id_mat", "id_materia", "materia_id"], "id_mat");

$col_alu_cap = primera_coincidencia($esq_capcal["cols"], ["id_alumno", "alumno_id", "matricula"], null);
$col_mat_cap = primera_coincidencia($esq_capcal["cols"], ["id_mat", "id_materia", "materia_id", "clave_mat", "cve_mat"], null);
$col_uni_cap = primera_coincidencia($esq_capcal["cols"], ["id_unidad", "unidad", "unidad_id", "num_unidad"], null);
$col_cal_cap = primera_coincidencia($esq_capcal["cols"], ["calificacion", "calif", "nota", "promedio"], null);

// Cargar catálogos para filtros
$lista_grupos = [];
$res_g = mysqli_query($conexion, "SELECT * FROM grupos");
if ($res_g) {
    while ($g = mysqli_fetch_assoc($res_g)) {
        $lista_grupos[] = $g;
    }
}

$lista_alumnos = [];
$order_a = in_array("apaterno", $esq_alumnos["cols"], true) ? " ORDER BY apaterno, amaterno, nombre" : "";
$res_a = mysqli_query($conexion, "SELECT * FROM alumnos{$order_a}");
if ($res_a) {
    while ($a = mysqli_fetch_assoc($res_a)) {
        $lista_alumnos[] = $a;
    }
}

$lista_materias = [];
$res_m = mysqli_query($conexion, "SELECT * FROM materias");
if ($res_m) {
    while ($m = mysqli_fetch_assoc($res_m)) {
        $lista_materias[] = $m;
    }
}

// Filtros seleccionados por GET
$rep_activa     = isset($_GET["rep"]) ? $_GET["rep"] : "grp_alumnos";
$filtro_grupo   = isset($_GET["id_grupo"]) ? trim($_GET["id_grupo"]) : "";
$filtro_alumno  = isset($_GET["id_alumno"]) ? trim($_GET["id_alumno"]) : "";
$filtro_materia = isset($_GET["id_mat"]) ? trim($_GET["id_mat"]) : "";

// 1. LISTA DE ALUMNOS POR GRUPO
$rows_alu_grupo = [];
$sql_ag = "
    SELECT a.*, g.`{$col_nom_grp}` AS nombre_grupo
    FROM alumnos a
    LEFT JOIN grupos g ON a.`{$col_grp_alu}` = g.`{$pk_grp}`
    WHERE 1=1
";
if ($filtro_grupo !== "") {
    $fg_esc = mysqli_real_escape_string($conexion, $filtro_grupo);
    $sql_ag .= " AND a.`{$col_grp_alu}` = '{$fg_esc}'";
}
$sql_ag .= " ORDER BY a.`{$col_grp_alu}` ASC" . (in_array("apaterno", $esq_alumnos["cols"], true) ? ", a.apaterno, a.amaterno, a.nombre" : "");
$res_ag = mysqli_query($conexion, $sql_ag);
if ($res_ag) {
    while ($r = mysqli_fetch_assoc($res_ag)) {
        $rows_alu_grupo[] = $r;
    }
}

// 2. LISTA DE MATERIAS POR ALUMNO
$rows_mat_alumno = [];
$sql_ma = "
    SELECT
        asg.*,
        a.apaterno,
        a.amaterno,
        a.nombre AS nombre_alumno,
        a.`{$col_grp_alu}` AS grp_alu,
        m.`{$col_nom_mat}` AS nombre_materia
    FROM asigmat asg
    LEFT JOIN alumnos a ON asg.`{$col_alu_asg}` = a.`{$pk_alu}`
    LEFT JOIN materias m ON asg.`{$col_mat_asg}` = m.`{$pk_mat}`
    WHERE 1=1
";
if ($filtro_alumno !== "") {
    $fa_esc = mysqli_real_escape_string($conexion, $filtro_alumno);
    $sql_ma .= " AND asg.`{$col_alu_asg}` = '{$fa_esc}'";
}
$sql_ma .= " ORDER BY asg.`{$col_alu_asg}` ASC, m.`{$col_nom_mat}` ASC";
$res_ma = mysqli_query($conexion, $sql_ma);
if ($res_ma) {
    while ($r = mysqli_fetch_assoc($res_ma)) {
        $rows_mat_alumno[] = $r;
    }
}

// 3. LISTA DE CALIFICACIONES POR MATERIA Y GRUPO
$rows_cal_mg = [];
if ($col_alu_cap !== null && $col_mat_cap !== null && $col_cal_cap !== null) {
    $sel_uni_mg = $col_uni_cap ? ", c.`{$col_uni_cap}` AS uni_val" : ", '—' AS uni_val";
    $join_mat_mg = in_array($pk_mat, $esq_materias["cols"], true)
        ? " LEFT JOIN materias m ON c.`{$col_mat_cap}` = m.`{$pk_mat}`"
        : "";
    $sel_nom_mat_mg = $join_mat_mg !== "" ? ", m.`{$col_nom_mat}` AS nombre_materia" : ", c.`{$col_mat_cap}` AS nombre_materia";

    $sql_cmg = "
        SELECT
            c.*,
            c.`{$col_alu_cap}` AS cod_alu,
            c.`{$col_mat_cap}` AS cod_mat,
            c.`{$col_cal_cap}` AS cal_val
            {$sel_uni_mg}
            {$sel_nom_mat_mg},
            a.apaterno,
            a.amaterno,
            a.nombre AS nombre_alumno,
            a.`{$col_grp_alu}` AS grp_alu,
            g.`{$col_nom_grp}` AS nombre_grupo
        FROM capcal c
        LEFT JOIN alumnos a ON c.`{$col_alu_cap}` = a.`{$pk_alu}`
        LEFT JOIN grupos g ON a.`{$col_grp_alu}` = g.`{$pk_grp}`
        {$join_mat_mg}
        WHERE 1=1
    ";
    if ($filtro_materia !== "") {
        $fm_esc = mysqli_real_escape_string($conexion, $filtro_materia);
        $sql_cmg .= " AND c.`{$col_mat_cap}` = '{$fm_esc}'";
    }
    if ($filtro_grupo !== "") {
        $fg_esc = mysqli_real_escape_string($conexion, $filtro_grupo);
        $sql_cmg .= " AND a.`{$col_grp_alu}` = '{$fg_esc}'";
    }
    $sql_cmg .= " ORDER BY a.`{$col_grp_alu}` ASC, c.`{$col_mat_cap}` ASC" . (in_array("apaterno", $esq_alumnos["cols"], true) ? ", a.apaterno, a.amaterno, a.nombre" : "");
    $res_cmg = mysqli_query($conexion, $sql_cmg);
    if ($res_cmg) {
        while ($r = mysqli_fetch_assoc($res_cmg)) {
            $rows_cal_mg[] = $r;
        }
    }
}

// 4. BOLETA DE CALIFICACIONES (POR ALUMNO)
$alumno_boleta = null;
$rows_boleta   = [];
$promedio_bol  = null;

$id_bol_target = $filtro_alumno;
if ($id_bol_target === "" && count($lista_alumnos) > 0) {
    $id_bol_target = (string)($lista_alumnos[0][$pk_alu] ?? "");
}

if ($id_bol_target !== "") {
    $fb_esc = mysqli_real_escape_string($conexion, $id_bol_target);
    $res_ab = mysqli_query(
        $conexion,
        "SELECT a.*, g.`{$col_nom_grp}` AS nombre_grupo
         FROM alumnos a
         LEFT JOIN grupos g ON a.`{$col_grp_alu}` = g.`{$pk_grp}`
         WHERE a.`{$pk_alu}` = '{$fb_esc}' LIMIT 1"
    );
    if ($res_ab && mysqli_num_rows($res_ab) > 0) {
        $alumno_boleta = mysqli_fetch_assoc($res_ab);
    }

    if ($col_alu_cap !== null && $col_mat_cap !== null && $col_cal_cap !== null) {
        $sel_uni_b = $col_uni_cap ? ", c.`{$col_uni_cap}` AS uni_val" : ", 'General' AS uni_val";
        $join_mat_b = in_array($pk_mat, $esq_materias["cols"], true)
            ? " LEFT JOIN materias m ON c.`{$col_mat_cap}` = m.`{$pk_mat}`"
            : "";
        $sel_nom_mat_b = $join_mat_b !== "" ? ", m.`{$col_nom_mat}` AS nombre_materia" : ", c.`{$col_mat_cap}` AS nombre_materia";
        $order_b = $join_mat_b !== "" ? "m.`{$col_nom_mat}` ASC" : "c.`{$col_mat_cap}` ASC";
        if ($col_uni_cap) {
            $order_b .= ", c.`{$col_uni_cap}` ASC";
        }

        $sql_bol = "
            SELECT
                c.`{$col_mat_cap}` AS cod_mat,
                c.`{$col_cal_cap}` AS cal_val
                {$sel_uni_b}
                {$sel_nom_mat_b}
            FROM capcal c
            {$join_mat_b}
            WHERE c.`{$col_alu_cap}` = '{$fb_esc}'
            ORDER BY {$order_b}
        ";

        $res_bol = mysqli_query($conexion, $sql_bol);
        $suma = 0;
        $cont = 0;
        if ($res_bol) {
            while ($rb = mysqli_fetch_assoc($res_bol)) {
                $rows_boleta[] = $rb;
                if (is_numeric($rb["cal_val"])) {
                    $suma += (float)$rb["cal_val"];
                    $cont++;
                }
            }
        }
        if ($cont > 0) {
            $promedio_bol = round($suma / $cont, 2);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Módulo de Reportes — Escuela</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fff5f7; color: #333; }
        h2, h3 { color: #d63384; margin-top: 15px; }
        .rep-card { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.06); margin-top: 15px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #fff; margin: 0; }
            .rep-card { box-shadow: none; padding: 0; }
        }
    </style>
</head>
<body class="p-3 m-0">
    <div class="no-print">
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
                <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Procesos</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="../procesos/pro_alumnos.php">Alumnos</a></li>
                    <li><a class="dropdown-item" href="../procesos/pro_grupos.php">Grupos</a></li>
                    <li><a class="dropdown-item" href="../procesos/pro_materias.php">Materias</a></li>
                    <li><a class="dropdown-item" href="../procesos/pro_profesores.php">Profesores</a></li>
                    <li><a class="dropdown-item" href="../procesos/pro_capcal.php">Capcal</a></li>
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
                <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Reporte</a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item active" href="../reportes/rep_general.php">Reportes Generales</a></li>
                    <li><a class="dropdown-item" href="../reportes/rep_alumnos.php">Alumnos</a></li>
                    <li><a class="dropdown-item" href="../reportes/rep_profesor.php">Profesores</a></li>
                    <li><a class="dropdown-item" href="../reportes/rep_materias.php">Materia</a></li>
                    <li><a class="dropdown-item" href="../reportes/rep_grupos.php">Grupos</a></li>
                </ul>
            </li>
        </ul>
    </div>

    <h2>Módulo de Reportes</h2>

    <ul class="nav nav-tabs mt-3 no-print">
        <li class="nav-item">
            <a class="nav-link <?= $rep_activa === 'grp_alumnos' ? 'active' : '' ?>" href="?rep=grp_alumnos">
                1. Alumnos por Grupo
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $rep_activa === 'mat_alumno' ? 'active' : '' ?>" href="?rep=mat_alumno">
                2. Materias por Alumno
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $rep_activa === 'cal_mg' ? 'active' : '' ?>" href="?rep=cal_mg">
                3. Calificaciones por Materia y Grupo
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $rep_activa === 'boleta' ? 'active' : '' ?>" href="?rep=boleta">
                4. Boleta de Calificaciones
            </a>
        </li>
    </ul>

    <div class="rep-card">
        <?php if ($rep_activa === 'grp_alumnos'): ?>
            <h3>Lista de Alumnos por Grupo</h3>
            <form method="GET" action="rep_general.php" class="row g-3 mb-3 no-print">
                <input type="hidden" name="rep" value="grp_alumnos" />
                <div class="col-md-5">
                    <label for="fg" class="form-label">Filtrar por grupo</label>
                    <select name="id_grupo" id="fg" class="form-select">
                        <option value="">Todos los grupos</option>
                        <?php foreach ($lista_grupos as $g): ?>
                            <?php $gid = (string)($g[$pk_grp] ?? ""); ?>
                            <option value="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>" <?= $filtro_grupo === $gid ? 'selected' : '' ?>>
                                <?= htmlspecialchars($gid . " - " . ($g[$col_nom_grp] ?? $gid), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir</button>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>ID / Matrícula</th>
                        <th>Alumno</th>
                        <th>Grupo</th>
                        <?php if ($tiene_estatus_alu): ?><th>Estado</th><?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows_alu_grupo) > 0): ?>
                        <?php foreach ($rows_alu_grupo as $r): ?>
                            <?php
                            $nomA = trim(($r["apaterno"] ?? "") . " " . ($r["amaterno"] ?? "") . ", " . ($r["nombre"] ?? ""));
                            $grpTxt = ($r[$col_grp_alu] ?? "") . (!empty($r["nombre_grupo"]) ? " — " . $r["nombre_grupo"] : "");
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($r[$pk_alu] ?? ""), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($nomA, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($grpTxt !== "" ? $grpTxt : "Sin grupo", ENT_QUOTES, 'UTF-8') ?></td>
                                <?php if ($tiene_estatus_alu): ?>
                                    <td><?= ((int)$r["estatus"] === 1) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Baja</span>' ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="<?= $tiene_estatus_alu ? 4 : 3 ?>" class="text-center text-muted">No hay alumnos para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($rep_activa === 'mat_alumno'): ?>
            <h3>Lista de Materias por Alumno</h3>
            <form method="GET" action="rep_general.php" class="row g-3 mb-3 no-print">
                <input type="hidden" name="rep" value="mat_alumno" />
                <div class="col-md-5">
                    <label for="fa" class="form-label">Filtrar por alumno</label>
                    <select name="id_alumno" id="fa" class="form-select">
                        <option value="">Todos los alumnos</option>
                        <?php foreach ($lista_alumnos as $a): ?>
                            <?php
                            $aid = (string)($a[$pk_alu] ?? "");
                            $anom = trim(($a["apaterno"] ?? "") . " " . ($a["amaterno"] ?? "") . ", " . ($a["nombre"] ?? ""));
                            ?>
                            <option value="<?= htmlspecialchars($aid, ENT_QUOTES, 'UTF-8') ?>" <?= $filtro_alumno === $aid ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aid . " - " . $anom, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir</button>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Alumno</th>
                        <th>Grupo</th>
                        <th>Materia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows_mat_alumno) > 0): ?>
                        <?php foreach ($rows_mat_alumno as $r): ?>
                            <?php
                            $nomA = trim(($r["apaterno"] ?? "") . " " . ($r["amaterno"] ?? "") . ", " . ($r["nombre_alumno"] ?? ""));
                            if ($nomA === ",") { $nomA = (string)($r[$col_alu_asg] ?? ""); }
                            $nomM = ($r[$col_mat_asg] ?? "") . " — " . ($r["nombre_materia"] ?? "Materia");
                            ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($r[$col_alu_asg] ?? "") . " — " . $nomA, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($r["grp_alu"] ?? "—"), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($nomM, ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="3" class="text-center text-muted">No hay materias asignadas para este filtro.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($rep_activa === 'cal_mg'): ?>
            <h3>Lista de Calificaciones por Materia y Grupo</h3>
            <form method="GET" action="rep_general.php" class="row g-3 mb-3 no-print">
                <input type="hidden" name="rep" value="cal_mg" />
                <div class="col-md-4">
                    <label for="fmg" class="form-label">Grupo</label>
                    <select name="id_grupo" id="fmg" class="form-select">
                        <option value="">Todos los grupos</option>
                        <?php foreach ($lista_grupos as $g): ?>
                            <?php $gid = (string)($g[$pk_grp] ?? ""); ?>
                            <option value="<?= htmlspecialchars($gid, ENT_QUOTES, 'UTF-8') ?>" <?= $filtro_grupo === $gid ? 'selected' : '' ?>>
                                <?= htmlspecialchars($gid . " - " . ($g[$col_nom_grp] ?? $gid), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="fmm" class="form-label">Materia</label>
                    <select name="id_mat" id="fmm" class="form-select">
                        <option value="">Todas las materias</option>
                        <?php foreach ($lista_materias as $m): ?>
                            <?php $mid = (string)($m[$pk_mat] ?? ""); ?>
                            <option value="<?= htmlspecialchars($mid, ENT_QUOTES, 'UTF-8') ?>" <?= $filtro_materia === $mid ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mid . " - " . ($m[$col_nom_mat] ?? $mid), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir</button>
                </div>
            </form>

            <table class="table table-bordered table-striped">
                <thead class="table-dark">
                    <tr>
                        <th>Grupo</th>
                        <th>Materia</th>
                        <th>Alumno</th>
                        <th>Unidad</th>
                        <th>Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($rows_cal_mg) > 0): ?>
                        <?php foreach ($rows_cal_mg as $r): ?>
                            <?php
                            $nomA = trim(($r["apaterno"] ?? "") . " " . ($r["amaterno"] ?? "") . ", " . ($r["nombre_alumno"] ?? ""));
                            if ($nomA === ",") { $nomA = (string)$r["cod_alu"]; }
                            $grpTxt = ($r["grp_alu"] ?? "—") . (!empty($r["nombre_grupo"]) ? " (" . $r["nombre_grupo"] . ")" : "");
                            $matTxt = (string)$r["cod_mat"] . " — " . ($r["nombre_materia"] ?? "Materia");
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($grpTxt, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($matTxt, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$r["cod_alu"] . " — " . $nomA, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)$r["uni_val"], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><strong><?= htmlspecialchars((string)$r["cal_val"], ENT_QUOTES, 'UTF-8') ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center text-muted">No hay calificaciones para el filtro seleccionado.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php elseif ($rep_activa === 'boleta'): ?>
            <h3>Boleta de Calificaciones</h3>
            <form method="GET" action="rep_general.php" class="row g-3 mb-3 no-print">
                <input type="hidden" name="rep" value="boleta" />
                <div class="col-md-6">
                    <label for="fb" class="form-label">Selecciona un alumno</label>
                    <select name="id_alumno" id="fb" class="form-select">
                        <?php foreach ($lista_alumnos as $a): ?>
                            <?php
                            $aid = (string)($a[$pk_alu] ?? "");
                            $anom = trim(($a["apaterno"] ?? "") . " " . ($a["amaterno"] ?? "") . ", " . ($a["nombre"] ?? ""));
                            ?>
                            <option value="<?= htmlspecialchars($aid, ENT_QUOTES, 'UTF-8') ?>" <?= $id_bol_target === $aid ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aid . " - " . $anom, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">Ver boleta</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()">Imprimir boleta</button>
                </div>
            </form>

            <?php if ($alumno_boleta): ?>
                <div class="border rounded p-3 mb-3 bg-light">
                    <div><strong>Alumno:</strong> <?= htmlspecialchars(trim(($alumno_boleta["apaterno"] ?? "") . " " . ($alumno_boleta["amaterno"] ?? "") . ", " . ($alumno_boleta["nombre"] ?? "")), ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Matrícula / ID:</strong> <?= htmlspecialchars((string)($alumno_boleta[$pk_alu] ?? ""), ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Grupo:</strong> <?= htmlspecialchars((string)($alumno_boleta[$col_grp_alu] ?? "—") . (!empty($alumno_boleta["nombre_grupo"]) ? " — " . $alumno_boleta["nombre_grupo"] : ""), ENT_QUOTES, 'UTF-8') ?></div>
                </div>

                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Materia</th>
                            <th>Unidad</th>
                            <th>Calificación</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($rows_boleta) > 0): ?>
                            <?php foreach ($rows_boleta as $rb): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)$rb["cod_mat"] . " — " . ($rb["nombre_materia"] ?? "Materia"), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$rb["uni_val"], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><strong><?= htmlspecialchars((string)$rb["cal_val"], ENT_QUOTES, 'UTF-8') ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="table-secondary">
                                <td colspan="2" class="text-end"><strong>Promedio General:</strong></td>
                                <td><strong><?= $promedio_bol !== null ? htmlspecialchars((string)$promedio_bol, ENT_QUOTES, 'UTF-8') : "—" ?></strong></td>
                            </tr>
                        <?php else: ?>
                            <tr><td colspan="3" class="text-center text-muted">Este alumno aún no tiene calificaciones registradas.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="alert alert-warning">No se encontró información del alumno seleccionado.</div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>