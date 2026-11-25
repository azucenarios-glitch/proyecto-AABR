<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

function obtener_esquema_pro_grp(PDO $pdo, string $tabla): array {
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

$conexion->exec("CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(50) NOT NULL
)");

$esq_grp = obtener_esquema_pro_grp($conexion, "grupos");
$cols_grp = $esq_grp["cols"];
$pk_grp = in_array("id_grupo", $cols_grp, true) ? "id_grupo" : ($esq_grp["pk"] ?? ($cols_grp[0] ?? "id"));
$col_nom_grp = in_array("grupo", $cols_grp, true) ? "grupo" : (in_array("nombre", $cols_grp, true) ? "nombre" : $pk_grp);

if (isset($_GET["del"])) {
    $id_del = trim((string)$_GET["del"]);
    if ($id_del !== "") {
        $stmt = $conexion->prepare("DELETE FROM grupos WHERE CAST(`{$pk_grp}` AS CHAR) = ?");
        $stmt->execute([$id_del]);
    }
    header("Location: /proyecto-AABR/Escuela/procesos/pro_grupos.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["editar"])) {
    $id = trim((string)($_POST["id"] ?? ""));
    $grupo = trim((string)($_POST["grupo"] ?? ""));
    if ($id !== "" && $grupo !== "") {
        $stmt = $conexion->prepare("UPDATE grupos SET `{$col_nom_grp}` = ? WHERE CAST(`{$pk_grp}` AS CHAR) = ?");
        $stmt->execute([$grupo, $id]);
    }
    header("Location: /proyecto-AABR/Escuela/procesos/pro_grupos.php");
    exit;
}

$editar = null;
if (isset($_GET["edit"])) {
    $id_edit = trim((string)$_GET["edit"]);
    if ($id_edit !== "") {
        $stmt = $conexion->prepare("SELECT * FROM grupos WHERE CAST(`{$pk_grp}` AS CHAR) = ? LIMIT 1");
        $stmt->execute([$id_edit]);
        $editar = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$grupos = $conexion->query("SELECT * FROM grupos ORDER BY `{$pk_grp}` ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos Grupos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2, h3 { color: var(--morado-claro); }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        table { border-collapse: collapse; width: 100%; background: var(--negro-panel); color: var(--blanco); margin: 15px 0; }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; text-align: left; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; vertical-align: middle; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
        .btn-act { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; color: var(--blanco) !important; border-radius: 4px; text-decoration: none !important; font-size: 13px; margin-right: 3px; }
        .btn-add { background-color: #6d28d9; border: 1px solid var(--morado-claro); }
        .btn-edit { background-color: #4c1d95; border: 1px solid var(--blanco); }
        .btn-del { background-color: #2e1065; border: 1px solid #a78bfa; }
        form.form-edicion { background: var(--negro-panel); color: var(--blanco); padding: 15px; border: 2px solid var(--morado-principal); border-radius: 6px; max-width: 480px; margin-bottom: 20px; }
        form.form-edicion input { width: 100%; padding: 8px; margin: 5px 0 12px; background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); border-radius: 4px; }
        button.btn-guardar { background: var(--morado-principal); color: var(--blanco); border: 1px solid var(--blanco); padding: 9px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        a { text-decoration: none; color: var(--morado-claro); }
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
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button">Procesos</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/procesos/pro_grupos.php">Grupos</a></li>
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

    <h2 class="mt-3">Procesos - Grupos</h2>

    <?php if ($editar): ?>
        <h3>Editar Grupo</h3>
        <form method="post" class="form-edicion">
            <input type="hidden" name="editar" value="1">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)($editar[$pk_grp] ?? ""), ENT_QUOTES, "UTF-8") ?>">
            Grupo:
            <input type="text" name="grupo" value="<?= htmlspecialchars((string)($editar[$col_nom_grp] ?? ""), ENT_QUOTES, "UTF-8") ?>" required>
            <button type="submit" class="btn-guardar">Guardar Cambios</button>
            <a href="/proyecto-AABR/Escuela/procesos/pro_grupos.php" style="margin-left: 10px;">Cancelar</a>
        </form>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Grupo</th>
                <th style="width: 120px; text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grupos as $g): ?>
                <?php $id_actual = (string)($g[$pk_grp] ?? ""); ?>
                <tr>
                    <td><?= htmlspecialchars($id_actual, ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars((string)($g[$col_nom_grp] ?? $id_actual), ENT_QUOTES, "UTF-8") ?></td>
                    <td style="text-align: center; white-space: nowrap;">
                        <a href="/proyecto-AABR/Escuela/registro/reg_grupos.php" class="btn-act btn-add" title="Nuevo"><i class="fa fa-plus"></i></a>
                        <a href="?edit=<?= urlencode($id_actual) ?>" class="btn-act btn-edit" title="Editar"><i class="fa fa-pen"></i></a>
                        <a href="?del=<?= urlencode($id_actual) ?>" class="btn-act btn-del" title="Eliminar" onclick="return confirm('Eliminar este grupo?')"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>