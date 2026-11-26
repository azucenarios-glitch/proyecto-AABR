<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

function obtener_esquema_rep_gen(PDO $pdo, string $tabla): array {
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

function detectar_col_rep_gen(array $cols, array $candidatos, $default = null) {
    foreach ($candidatos as $c) {
        if (in_array($c, $cols, true)) {
            return $c;
        }
    }
    return $default;
}

$esq_cap = obtener_esquema_rep_gen($conexion, "capcal");
$esq_alu = obtener_esquema_rep_gen($conexion, "alumnos");
$esq_mat = obtener_esquema_rep_gen($conexion, "materias");

$cols_cap = $esq_cap["cols"];
$pk_cap   = detectar_col_rep_gen($cols_cap, ["id", "id_capcal", "id_calificacion"], $esq_cap["pk"] ?? ($cols_cap[0] ?? "id"));
$col_alu  = detectar_col_rep_gen($cols_cap, ["id_alumno", "alumno_id", "matricula", "id_alu"]);
$col_mat  = detectar_col_rep_gen($cols_cap, ["id_mat", "id_materia", "materia_id", "clave_mat", "cve_mat"]);
$col_uni  = detectar_col_rep_gen($cols_cap, ["unidad", "id_unidad", "unidad_id", "num_unidad"]);
$col_cal  = detectar_col_rep_gen($cols_cap, ["calificacion", "calif", "nota", "promedio"]);

$pk_alu = detectar_col_rep_gen($esq_alu["cols"], ["id_alumno", "id", "matricula"], $esq_alu["pk"] ?? "id_alumno");
$pk_mat = detectar_col_rep_gen($esq_mat["cols"], ["id_mat", "id_materia", "id", "clave"], $esq_mat["pk"] ?? "id_mat");

$registros = [];
if (count($cols_cap) > 0) {
    $expr_alu = $col_alu ? "c.`{$col_alu}`" : "''";
    $expr_mat = $col_mat ? "c.`{$col_mat}`" : "''";
    $expr_uni = $col_uni ? "c.`{$col_uni}`" : "'-'";
    $expr_cal = $col_cal ? "c.`{$col_cal}`" : "0";

    $joins = "";
    $extra_sel = "";

    if ($col_alu !== null && count($esq_alu["cols"]) > 0) {
        $joins .= " LEFT JOIN alumnos a ON CAST(c.`{$col_alu}` AS CHAR) = CAST(a.`{$pk_alu}` AS CHAR)";
        $extra_sel .= ", a.*";
    }
    if ($col_mat !== null && count($esq_mat["cols"]) > 0) {
        $joins .= " LEFT JOIN materias m ON CAST(c.`{$col_mat}` AS CHAR) = CAST(m.`{$pk_mat}` AS CHAR)";
        $extra_sel .= ", m.*";
    }

    $sql = "
        SELECT
            c.`{$pk_cap}` AS id_reg,
            {$expr_alu} AS cod_alu,
            {$expr_mat} AS cod_mat,
            {$expr_uni} AS cod_uni,
            {$expr_cal} AS calif
            {$extra_sel}
        FROM capcal c
        {$joins}
        ORDER BY c.`{$pk_cap}` DESC
    ";
    $registros = $conexion->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

$total = count($registros);
$suma_cal = 0.0;
$cant_cal = 0;
foreach ($registros as $r) {
    if (isset($r["calif"]) && is_numeric($r["calif"])) {
        $suma_cal += (float)$r["calif"];
        $cant_cal++;
    }
}
$promedio_general = $cant_cal > 0 ? ($suma_cal / $cant_cal) : 0.0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reporte General</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff; --rojo-baja: #dc2626; --rojo-fondo: #450a0a;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h1 { text-align: center; color: var(--morado-claro); border-bottom: 2px solid var(--morado-principal); padding-bottom: 10px; margin-top: 22px; font-size: 1.8rem; }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; background: var(--negro-panel); color: var(--blanco); }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 12px; text-align: left; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
        .calif-baja { background-color: var(--rojo-fondo); color: #fca5a5 !important; border: 1px solid var(--rojo-baja); padding: 3px 8px; border-radius: 4px; display: inline-block; }
        .resumen { margin-top: 15px; font-weight: bold; color: var(--morado-claro); display: flex; gap: 24px; flex-wrap: wrap; }
        @media print {
            .nav-pills, button, a { display: none; }
            body { background: #ffffff; color: #000000; }
            th { background: #3b1478; color: #ffffff; }
            td { color: #000000; }
        }
    </style>
</head>
<body class="p-3 m-0 border-0">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link" href="/proyecto-AABR/Escuela/index.php">Inicio</a></li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Baja</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_profesores.php">Profesor</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_alumnos.php">Alumnos</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Procesos</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_materias.php">Materias</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_asignat.php">Asignat</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_insgrup.php">Insgrup</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_vista.php">Vista Alumno</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button">Registro</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_alumno.php">Alumno</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_unidad.php">Unidad</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_usuarios.php">Usuarios</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button">Reporte</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/reportes/rep_general.php">General</a></li>
            </ul>
        </li>
    </ul>

    <h1>REPORTE GENERAL DE CALIFICACIONES</h1>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Alumno</th>
                <th>Materia</th>
                <th>Unidad</th>
                <th>Calificacion</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total > 0): ?>
                <?php foreach ($registros as $r): ?>
                    <?php
                    $nom_alu = trim(($r["apaterno"] ?? "") . " " . ($r["amaterno"] ?? "") . ", " . ($r["nombre"] ?? ""));
                    if ($nom_alu === ",") {
                        $nom_alu = (string)($r["nombre_completo"] ?? $r["cod_alu"]);
                    }
                    $alu_txt = ((string)$r["cod_alu"] !== "") ? ((string)$r["cod_alu"] . " - " . $nom_alu) : $nom_alu;

                    $nom_mat = $r["descripcion"] ?? $r["nombre_mat"] ?? $r["materia"] ?? $r["asignatura"] ?? $r["cod_mat"];
                    $mat_txt = ((string)$r["cod_mat"] !== (string)$nom_mat && (string)$r["cod_mat"] !== "")
                        ? ((string)$r["cod_mat"] . " - " . (string)$nom_mat)
                        : (string)$nom_mat;

                    $cal_num = is_numeric($r["calif"]) ? (float)$r["calif"] : 0.0;
                    $es_baja = is_numeric($r["calif"]) && ($cal_num < 6.0);
                    ?>
                    <tr>
                        <td><?= htmlspecialchars((string)$r["id_reg"], ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($alu_txt, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($mat_txt, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars((string)$r["cod_uni"], ENT_QUOTES, "UTF-8") ?></td>
                        <td>
                            <strong class="<?= $es_baja ? 'calif-baja' : '' ?>">
                                <?= is_numeric($r["calif"]) ? number_format($cal_num, 1) : htmlspecialchars((string)$r["calif"], ENT_QUOTES, "UTF-8") ?>
                            </strong>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No hay registros disponibles en el reporte general.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="resumen">
        <span>Total de registros: <?= (int)$total ?></span>
        <span>Promedio general: <?= number_format($promedio_general, 2) ?></span>
    </div>
</body>
</html>