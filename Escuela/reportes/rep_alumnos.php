<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

function obtener_esquema_rep_alu(PDO $pdo, string $tabla): array {
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

$esq_alu = obtener_esquema_rep_alu($conexion, "alumnos");
$cols_alu = $esq_alu["cols"];
$pk_alu = in_array("id_alumno", $cols_alu, true) ? "id_alumno" : ($esq_alu["pk"] ?? ($cols_alu[0] ?? "id_alumno"));
$col_est = in_array("estatus", $cols_alu, true) ? "estatus" : (in_array("estado", $cols_alu, true) ? "estado" : null);

$filtro_estatus = $_GET["estatus"] ?? "todos";
$alumnos = [];

if (count($cols_alu) > 0) {
    $sql = "SELECT * FROM alumnos";
    $params = [];

    if ($col_est !== null) {
        if ($filtro_estatus === "activos") {
            $sql .= " WHERE (`{$col_est}` = 1 OR LOWER(CAST(`{$col_est}` AS CHAR)) = 'activo')";
        } elseif ($filtro_estatus === "baja") {
            $sql .= " WHERE (`{$col_est}` = 0 OR LOWER(CAST(`{$col_est}` AS CHAR)) IN ('inactivo', 'baja'))";
        }
    }

    $sql .= in_array("apaterno", $cols_alu, true) ? " ORDER BY apaterno, amaterno, nombre" : " ORDER BY `{$pk_alu}` ASC";
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $alumnos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$total = count($alumnos);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reporte Alumnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h1 { text-align: center; color: var(--morado-claro); border-bottom: 2px solid var(--morado-principal); padding-bottom: 10px; margin-top: 22px; font-size: 1.8rem; }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .filtros { margin-top: 16px; display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .btn-filtro { background: var(--negro-panel); color: var(--blanco) !important; border: 1px solid var(--borde-morado); padding: 6px 12px; border-radius: 4px; text-decoration: none; font-size: 14px; font-weight: bold; }
        .btn-filtro.activo, .btn-filtro:hover { background: var(--morado-principal); border-color: var(--morado-claro); }
        table { border-collapse: collapse; width: 100%; margin-top: 16px; background: var(--negro-panel); color: var(--blanco); }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 12px; text-align: left; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
        .badge-est { display: inline-block; padding: 3px 9px; border-radius: 4px; background: var(--morado-principal); color: var(--blanco); font-weight: bold; font-size: 12px; }
        .badge-baj { display: inline-block; padding: 3px 9px; border-radius: 4px; background: var(--morado-oscuro); color: var(--morado-claro); border: 1px solid var(--borde-morado); font-weight: bold; font-size: 12px; }
        .total { margin-top: 15px; font-weight: bold; color: var(--morado-claro); }
        @media print {
            .nav-pills, .filtros, button, a { display: none; }
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
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/reportes/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/reportes/rep_general.php">General</a></li>
            </ul>
        </li>
    </ul>

    <h1>REPORTE DE ALUMNOS</h1>

    <div class="filtros">
        <a href="?estatus=todos" class="btn-filtro <?= $filtro_estatus === 'todos' ? 'activo' : '' ?>">Todos</a>
        <a href="?estatus=activos" class="btn-filtro <?= $filtro_estatus === 'activos' ? 'activo' : '' ?>">Activos</a>
        <a href="?estatus=baja" class="btn-filtro <?= $filtro_estatus === 'baja' ? 'activo' : '' ?>">Baja</a>
        <button type="button" class="btn-filtro" onclick="window.print()">Imprimir</button>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Correo</th>
                <th>Telefono</th>
                <th>Grupo</th>
                <th>Estatus</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($total > 0): ?>
                <?php foreach ($alumnos as $a): ?>
                    <?php
                    $id_val = (string)($a[$pk_alu] ?? "");
                    $nombre_val = trim(($a["apaterno"] ?? "") . " " . ($a["amaterno"] ?? "") . ", " . ($a["nombre"] ?? ""));
                    if ($nombre_val === ",") {
                        $nombre_val = (string)($a["nombre_completo"] ?? $a["nombre"] ?? $id_val);
                    }
                    $est_raw = $col_est !== null ? ($a[$col_est] ?? 1) : 1;
                    $es_activo = ((string)$est_raw === "1" || strtolower((string)$est_raw) === "activo");
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($id_val, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($nombre_val, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars((string)($a["mail"] ?? $a["correo"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars((string)($a["tel"] ?? $a["telefono"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars((string)($a["id_grupo"] ?? $a["grupo"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                        <td>
                            <?php if ($es_activo): ?>
                                <span class="badge-est">Activo</span>
                            <?php else: ?>
                                <span class="badge-baj">Baja</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">No hay alumnos para mostrar.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p class="total">Total de alumnos: <?= (int)$total ?></p>
</body>
</html>