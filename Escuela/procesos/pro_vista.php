<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

$cal_minima_aprobatoria = 6.0;

function obtener_esquema_vista(PDO $pdo, string $tabla): array {
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

function detectar_col_vista(array $cols, array $candidatos, $default = null) {
    foreach ($candidatos as $c) {
        if (in_array($c, $cols, true)) {
            return $c;
        }
    }
    return $default;
}

$esq_capcal  = obtener_esquema_vista($conexion, "capcal");
$esq_alumnos = obtener_esquema_vista($conexion, "alumnos");
$esq_mat     = obtener_esquema_vista($conexion, "materias");
$esq_uni     = obtener_esquema_vista($conexion, "unidad");

$cols_cap = $esq_capcal["cols"];
$pk_cap   = detectar_col_vista($cols_cap, ["id", "id_capcal", "id_calificacion"], $esq_capcal["pk"] ?? ($cols_cap[0] ?? "id"));

$col_alu_cap = detectar_col_vista($cols_cap, ["id_alumno", "alumno_id", "matricula", "id_alu"]);
$col_mat_cap = detectar_col_vista($cols_cap, ["id_mat", "id_materia", "materia_id", "clave_mat", "cve_mat"]);
$col_uni_cap = detectar_col_vista($cols_cap, ["unidad", "id_unidad", "unidad_id", "num_unidad"]);
$col_cal_cap = detectar_col_vista($cols_cap, ["calificacion", "calif", "nota", "promedio"]);

$pk_alu = detectar_col_vista($esq_alumnos["cols"], ["id_alumno", "id", "matricula"], $esq_alumnos["pk"] ?? "id_alumno");
$pk_mat = detectar_col_vista($esq_mat["cols"], ["id_mat", "id_materia", "id", "clave"], $esq_mat["pk"] ?? "id_mat");

$lista_alumnos_vista = [];
if (count($esq_alumnos["cols"]) > 0) {
    $lista_alumnos_vista = $conexion->query("SELECT * FROM alumnos ORDER BY `{$pk_alu}` ASC")->fetchAll(PDO::FETCH_ASSOC);
}

$id_alumno = null;
if (isset($_GET["id_alumno"]) && $_GET["id_alumno"] !== "") {
    $id_alumno = trim((string)$_GET["id_alumno"]);
} elseif (isset($_SESSION["id_alumno"]) && $_SESSION["id_alumno"] !== "") {
    $id_alumno = trim((string)$_SESSION["id_alumno"]);
} else {
    if ($col_alu_cap !== null && count($cols_cap) > 0) {
        $row_cc = $conexion->query("SELECT `{$col_alu_cap}` AS alu_id FROM capcal WHERE `{$col_alu_cap}` IS NOT NULL AND `{$col_alu_cap}` <> '' ORDER BY `{$pk_cap}` DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($row_cc && isset($row_cc["alu_id"])) {
            $id_alumno = trim((string)$row_cc["alu_id"]);
        }
    }
    if (($id_alumno === null || $id_alumno === "") && count($lista_alumnos_vista) > 0) {
        $id_alumno = (string)($lista_alumnos_vista[0][$pk_alu] ?? "");
    }
}

$alumno = null;
if ($id_alumno !== null && $id_alumno !== "" && count($esq_alumnos["cols"]) > 0) {
    $stmt_al = $conexion->prepare("SELECT * FROM alumnos WHERE CAST(`{$pk_alu}` AS CHAR) = :id_al LIMIT 1");
    $stmt_al->execute(["id_al" => $id_alumno]);
    $alumno = $stmt_al->fetch(PDO::FETCH_ASSOC) ?: null;
}

$claves_busqueda = [];
if ($id_alumno !== null && $id_alumno !== "") {
    $claves_busqueda[] = $id_alumno;
}
if ($alumno) {
    foreach ([$pk_alu, "matricula", "id_alumno", "id"] as $campo_extra) {
        if (isset($alumno[$campo_extra]) && (string)$alumno[$campo_extra] !== "") {
            $claves_busqueda[] = (string)$alumno[$campo_extra];
        }
    }
}
$claves_busqueda = array_values(array_unique($claves_busqueda));

$calificaciones = [];
if (count($claves_busqueda) > 0 && count($cols_cap) > 0 && $col_alu_cap !== null) {
    $expr_mat = $col_mat_cap ? "c.`{$col_mat_cap}`" : "''";
    $expr_uni = $col_uni_cap ? "c.`{$col_uni_cap}`" : "'-'";
    $expr_cal = $col_cal_cap ? "c.`{$col_cal_cap}`" : "0";

    $joins = "";
    $extra_sel = "";

    if ($col_mat_cap !== null && count($esq_mat["cols"]) > 0) {
        $joins .= " LEFT JOIN materias m ON CAST(c.`{$col_mat_cap}` AS CHAR) = CAST(m.`{$pk_mat}` AS CHAR)";
        $extra_sel .= ", m.*";
    }

    if ($col_uni_cap !== null && count($esq_uni["cols"]) > 0) {
        $pk_u = detectar_col_vista($esq_uni["cols"], ["id_unidad", "unidad", "id"], $esq_uni["pk"] ?? $esq_uni["cols"][0]);
        $joins .= " LEFT JOIN unidad u ON CAST(c.`{$col_uni_cap}` AS CHAR) = CAST(u.`{$pk_u}` AS CHAR)";
        $col_nom_u = detectar_col_vista($esq_uni["cols"], ["nombre", "descripcion", "unidad"]);
        if ($col_nom_u) {
            $extra_sel .= ", u.`{$col_nom_u}` AS nombre_unidad_tbl";
        }
    }

    $placeholders = implode(", ", array_fill(0, count($claves_busqueda), "?"));
    $sql_cal = "
        SELECT
            c.`{$pk_cap}` AS id_reg,
            c.`{$col_alu_cap}` AS id_alu_reg,
            {$expr_mat} AS cod_mat,
            {$expr_uni} AS cod_uni,
            {$expr_cal} AS calif
            {$extra_sel}
        FROM capcal c
        {$joins}
        WHERE TRIM(CAST(c.`{$col_alu_cap}` AS CHAR)) IN ({$placeholders})
        ORDER BY c.`{$pk_cap}` ASC
    ";

    $stmt_cal = $conexion->prepare($sql_cal);
    $stmt_cal->execute($claves_busqueda);
    $calificaciones = $stmt_cal->fetchAll(PDO::FETCH_ASSOC);
}

$promedio = 0.00;
$conteo_num = 0;
foreach ($calificaciones as $fila) {
    if (isset($fila["calif"]) && is_numeric($fila["calif"])) {
        $promedio += (float)$fila["calif"];
        $conteo_num++;
    }
}
if ($conteo_num > 0) {
    $promedio = $promedio / $conteo_num;
}
$promedioEsMenor = ($conteo_num > 0 && $promedio < $cal_minima_aprobatoria);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Mis Calificaciones - Alumno</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff; --rojo-baja: #dc2626; --rojo-fondo: #450a0a;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2, h3 { color: var(--morado-claro); }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; margin-bottom: 18px; flex-wrap: wrap; gap: 10px; }
        .btn-salir { background: var(--morado-oscuro); color: var(--blanco) !important; border: 1px solid var(--morado-claro); padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; }
        .btn-salir:hover { background: var(--morado-principal); }
        .tarjeta-alumno { background: var(--negro-panel); border: 1px solid var(--borde-morado); border-radius: 6px; padding: 16px; margin-bottom: 18px; }
        .selector-box { background: var(--negro-panel); border: 1px solid var(--borde-morado); border-radius: 6px; padding: 12px 16px; margin-bottom: 18px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .selector-box select { background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); border-radius: 4px; padding: 6px 10px; min-width: 240px; }
        .selector-box button { background: var(--morado-principal); color: var(--blanco); border: 1px solid var(--blanco); border-radius: 4px; padding: 6px 14px; font-weight: bold; cursor: pointer; }
        .badge-activo { display: inline-block; padding: 4px 10px; border-radius: 4px; background: var(--morado-principal); color: var(--blanco); font-weight: bold; font-size: 13px; }
        table { border-collapse: collapse; width: 100%; background: var(--negro-panel); color: var(--blanco); margin: 15px 0; }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; text-align: left; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; vertical-align: middle; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
        .calif-baja { background-color: var(--rojo-fondo); color: #fca5a5 !important; border: 1px solid var(--rojo-baja); padding: 3px 8px; border-radius: 4px; display: inline-block; }
        .promedio-box { margin-top: 22px; text-align: center; background: var(--negro-panel); border: 2px solid var(--morado-principal); border-radius: 6px; padding: 14px; max-width: 260px; margin-left: auto; margin-right: auto; }
        .promedio-valor { font-size: 1.5rem; font-weight: bold; color: var(--blanco); margin: 4px 0 0; }
    </style>
</head>
<body class="p-3 m-0 border-0">
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
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_asignat.php">Asignat</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_insgrup.php">Insgrup</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/procesos/pro_vista.php">Vista Alumno</a></li>
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

    <div class="top-bar">
        <h2>Mis Calificaciones - Alumno</h2>
        <div class="d-flex gap-2">
            <a href="/proyecto-AABR/Escuela/procesos/pro_capcal.php" class="btn-salir"><i class="fa fa-pen"></i> Capturar</a>
            <a href="/proyecto-AABR/Escuela/logout.php" class="btn-salir"><i class="fa fa-right-from-bracket"></i> Cerrar sesion</a>
        </div>
    </div>

    <?php if (count($lista_alumnos_vista) > 0): ?>
        <form method="GET" action="/proyecto-AABR/Escuela/procesos/pro_vista.php" class="selector-box">
            <label for="id_alumno" class="m-0 fw-bold" style="color: var(--morado-claro);">Consultar alumno:</label>
            <select name="id_alumno" id="id_alumno">
                <?php foreach ($lista_alumnos_vista as $al_opt): ?>
                    <?php
                    $val_opt = (string)($al_opt[$pk_alu] ?? "");
                    $nom_opt = trim(($al_opt["apaterno"] ?? "") . " " . ($al_opt["amaterno"] ?? "") . ", " . ($al_opt["nombre"] ?? ""));
                    if ($nom_opt === ",") {
                        $nom_opt = (string)($al_opt["nombre_completo"] ?? $al_opt["nombre"] ?? $val_opt);
                    }
                    ?>
                    <?php if ($val_opt !== ""): ?>
                        <option value="<?= htmlspecialchars($val_opt, ENT_QUOTES, "UTF-8") ?>" <?= ((string)$id_alumno === $val_opt) ? "selected" : "" ?>>
                            <?= htmlspecialchars($val_opt . " - " . $nom_opt, ENT_QUOTES, "UTF-8") ?>
                        </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
            <button type="submit">Ver calificaciones</button>
        </form>
    <?php endif; ?>

    <?php if ($alumno): ?>
        <?php
        $id_al_mostrar = (string)($alumno[$pk_alu] ?? $id_alumno);
        $nombre_al = trim(($alumno["apaterno"] ?? "") . " " . ($alumno["amaterno"] ?? "") . ", " . ($alumno["nombre"] ?? ""));
        if ($nombre_al === ",") {
            $nombre_al = (string)($alumno["nombre_completo"] ?? $alumno["nombre"] ?? $id_al_mostrar);
        }
        $est_raw = $alumno["estatus"] ?? 1;
        $est_texto = ((string)$est_raw === "1" || strtolower((string)$est_raw) === "activo") ? "Activo" : "Inactivo";
        ?>
        <div class="tarjeta-alumno">
            <p class="mb-1">ID Alumno: <strong><?= htmlspecialchars($id_al_mostrar, ENT_QUOTES, "UTF-8") ?></strong></p>
            <h3 class="mb-2"><?= htmlspecialchars($nombre_al, ENT_QUOTES, "UTF-8") ?></h3>
            <p class="mb-1">Estatus</p>
            <span class="badge-activo"><?= htmlspecialchars($est_texto, ENT_QUOTES, "UTF-8") ?></span>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID Registro</th>
                    <th>ID Materia</th>
                    <th>Materia</th>
                    <th>Unidad</th>
                    <th>Calificacion</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($calificaciones) > 0): ?>
                    <?php foreach ($calificaciones as $fila): ?>
                        <?php
                        $nom_mat = $fila["nombre"] ?? $fila["materia"] ?? $fila["asignatura"] ?? $fila["descripcion"] ?? $fila["cod_mat"];
                        $texto_mat = ((string)$fila["cod_mat"] !== (string)$nom_mat && (string)$fila["cod_mat"] !== "")
                            ? ((string)$fila["cod_mat"] . " - " . (string)$nom_mat)
                            : (string)$nom_mat;
                        $texto_uni = $fila["nombre_unidad_tbl"] ?? $fila["cod_uni"] ?? "-";
                        $calNum = is_numeric($fila["calif"]) ? (float)$fila["calif"] : 0.0;
                        $esMenor = is_numeric($fila["calif"]) && ($calNum < $cal_minima_aprobatoria);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars((string)$fila["id_reg"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars((string)$fila["cod_mat"], ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars($texto_mat, ENT_QUOTES, "UTF-8") ?></td>
                            <td><?= htmlspecialchars((string)$texto_uni, ENT_QUOTES, "UTF-8") ?></td>
                            <td>
                                <strong class="<?= $esMenor ? 'calif-baja' : '' ?>">
                                    <?= is_numeric($fila["calif"]) ? number_format($calNum, 1) : htmlspecialchars((string)$fila["calif"], ENT_QUOTES, "UTF-8") ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No hay calificaciones registradas en <code>capcal</code> para este alumno.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="promedio-box">
            <h3>Promedio</h3>
            <p class="promedio-valor <?= $promedioEsMenor ? 'calif-baja' : '' ?>"><?= number_format($promedio, 2) ?></p>
        </div>
    <?php else: ?>
        <div class="tarjeta-alumno">
            <p class="mb-0">No se encontro informacion del alumno.</p>
        </div>
    <?php endif; ?>
</body>
</html>