<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

function obtener_esquema_ba_alu_filtro(PDO $pdo, string $tabla): array {
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

$esq_alu = obtener_esquema_ba_alu_filtro($conexion, "alumnos");
$cols_alu = $esq_alu["cols"];
$pk_alu = in_array("id_alumno", $cols_alu, true) ? "id_alumno" : ($esq_alu["pk"] ?? ($cols_alu[0] ?? "id_alumno"));
$col_est = in_array("estatus", $cols_alu, true) ? "estatus" : (in_array("estado", $cols_alu, true) ? "estado" : null);

$mensaje = "";

if ($col_est !== null && count($cols_alu) > 0 && isset($_GET["reactivar"])) {
    $id_act = trim((string)$_GET["reactivar"]);
    if ($id_act !== "") {
        $stmt = $conexion->prepare("UPDATE alumnos SET `{$col_est}` = 1 WHERE CAST(`{$pk_alu}` AS CHAR) = ?");
        $stmt->execute([$id_act]);
        header("Location: /proyecto-AABR/Escuela/Baja/ba_alumnos.php?msg=reactivado");
        exit;
    }
}

if (isset($_GET["msg"]) && $_GET["msg"] === "reactivado") {
    $mensaje = "Alumno reactivado correctamente.";
}

$alumnos_baja = [];
if (count($cols_alu) > 0 && $col_est !== null) {
    $order = in_array("apaterno", $cols_alu, true) ? "ORDER BY apaterno, amaterno, nombre" : "ORDER BY `{$pk_alu}` ASC";
    $stmt_b = $conexion->query("
        SELECT * FROM alumnos
        WHERE TRIM(CAST(`{$col_est}` AS CHAR)) IN ('0', 'Baja', 'baja', 'Inactivo', 'inactivo')
        {$order}
    ");
    $alumnos_baja = $stmt_b ? $stmt_b->fetchAll(PDO::FETCH_ASSOC) : [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Baja - Alumnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2 { color: var(--morado-claro); margin-top: 20px; }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .msg-ok { background: var(--morado-oscuro); color: var(--blanco); border: 1px solid var(--morado-claro); padding: 10px 14px; border-radius: 6px; margin-top: 14px; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; background: var(--negro-panel); color: var(--blanco); margin: 15px 0; }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; text-align: left; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; vertical-align: middle; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
        .badge-baj { display: inline-block; padding: 3px 9px; border-radius: 4px; background: var(--morado-oscuro); color: var(--morado-claro); border: 1px solid var(--borde-morado); font-weight: bold; font-size: 12px; }
        .btn-accion { background: var(--morado-oscuro); color: var(--blanco) !important; border: 1px solid var(--morado-claro); padding: 5px 10px; border-radius: 4px; text-decoration: none; font-size: 13px; font-weight: bold; display: inline-block; }
        .btn-accion:hover { background: var(--morado-principal); }
    </style>
</head>
<body class="p-3 m-0 border-0">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link" href="/proyecto-AABR/Escuela/index.php">Inicio</a></li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button">Baja</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/Baja/ba_profesores.php">Profesor</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/Baja/ba_alumnos.php">Alumnos</a></li>
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

    <h2>Alumnos Dados de Baja</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="msg-ok"><?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Grupo</th>
                <th>Estatus</th>
                <th style="width: 140px; text-align: center;">Accion</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($alumnos_baja) > 0): ?>
                <?php foreach ($alumnos_baja as $a): ?>
                    <?php
                    $id_val = (string)($a[$pk_alu] ?? "");
                    $nombre_val = trim(($a["apaterno"] ?? "") . " " . ($a["amaterno"] ?? "") . ", " . ($a["nombre"] ?? ""));
                    if ($nombre_val === ",") {
                        $nombre_val = (string)($a["nombre_completo"] ?? $a["nombre"] ?? $id_val);
                    }
                    $grupo_val = (string)($a["id_grupo"] ?? $a["grupo"] ?? "-");
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($id_val, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($nombre_val, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($grupo_val, ENT_QUOTES, "UTF-8") ?></td>
                        <td><span class="badge-baj">Baja</span></td>
                        <td style="text-align: center;">
                            <a href="?reactivar=<?= urlencode($id_val) ?>" class="btn-accion">Reactivar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">No hay alumnos dados de baja.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>