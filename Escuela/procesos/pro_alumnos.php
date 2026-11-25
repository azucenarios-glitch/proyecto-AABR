<?php
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

$col_id = "id_alumno";

$conexion->exec("CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(50) NOT NULL
)");

$conexion->exec("CREATE TABLE IF NOT EXISTS alumnos (
    `{$col_id}` INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apaterno VARCHAR(50) NOT NULL,
    amaterno VARCHAR(50),
    dom VARCHAR(100),
    mail VARCHAR(100),
    tel VARCHAR(20),
    id_grupo VARCHAR(50),
    estatus INT DEFAULT 1
)");

$check_col = $conexion->query("SHOW COLUMNS FROM alumnos LIKE 'estatus'")->fetchAll();
if (count($check_col) === 0) {
    $conexion->exec("ALTER TABLE alumnos ADD estatus INT DEFAULT 1");
}

// Baja logica
if (isset($_GET["del"])) {
    $id_del = (int)$_GET["del"];
    if ($id_del > 0) {
        $stmt = $conexion->prepare("UPDATE alumnos SET estatus = 0 WHERE `{$col_id}` = ?");
        $stmt->execute([$id_del]);
    }
    header("Location: /proyecto-AABR/Escuela/procesos/pro_alumnos.php");
    exit;
}

// Guardar edicion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["editar"])) {
    $id       = (int)($_POST["id"] ?? 0);
    $nombre   = trim((string)($_POST["nombre"] ?? ""));
    $apaterno = trim((string)($_POST["apaterno"] ?? ""));
    $amaterno = trim((string)($_POST["amaterno"] ?? ""));
    $dom      = trim((string)($_POST["dom"] ?? ""));
    $mail     = trim((string)($_POST["mail"] ?? ""));
    $tel      = trim((string)($_POST["tel"] ?? ""));
    $id_grupo = trim((string)($_POST["id_grupo"] ?? ""));

    if ($id > 0) {
        $stmt = $conexion->prepare("
            UPDATE alumnos
            SET nombre = ?, apaterno = ?, amaterno = ?, dom = ?, mail = ?, tel = ?, id_grupo = ?
            WHERE `{$col_id}` = ?
        ");
        $stmt->execute([$nombre, $apaterno, $amaterno, $dom, $mail, $tel, $id_grupo, $id]);
    }
    header("Location: /proyecto-AABR/Escuela/procesos/pro_alumnos.php");
    exit;
}

// Cargar registro para editar
$editar = null;
if (isset($_GET["edit"])) {
    $id_edit = (int)$_GET["edit"];
    if ($id_edit > 0) {
        $stmt = $conexion->prepare("SELECT * FROM alumnos WHERE `{$col_id}` = ? LIMIT 1");
        $stmt->execute([$id_edit]);
        $editar = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$alumnos = $conexion->query("SELECT * FROM alumnos WHERE estatus = 1 ORDER BY apaterno, amaterno, nombre")->fetchAll(PDO::FETCH_ASSOC);
$grupos  = $conexion->query("SELECT DISTINCT grupo FROM grupos ORDER BY grupo")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos Alumnos</title>
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
        .badge-activo { display: inline-block; padding: 3px 9px; border-radius: 4px; background: var(--morado-principal); color: var(--blanco); font-weight: bold; font-size: 12px; }
        .btn-act { display: inline-flex; align-items: center; justify-content: center; width: 28px; height: 28px; color: var(--blanco) !important; border-radius: 4px; text-decoration: none !important; font-size: 13px; margin-right: 3px; }
        .btn-add { background-color: #6d28d9; border: 1px solid var(--morado-claro); }
        .btn-edit { background-color: #4c1d95; border: 1px solid var(--blanco); }
        .btn-del { background-color: #2e1065; border: 1px solid #a78bfa; }
        .btn-act:hover { filter: brightness(1.2); }
        form.form-edicion { background: var(--negro-panel); color: var(--blanco); padding: 15px; border: 2px solid var(--morado-principal); border-radius: 6px; max-width: 500px; margin-bottom: 20px; }
        form.form-edicion input, form.form-edicion select { width: 100%; padding: 8px; margin: 5px 0 10px; background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); border-radius: 4px; }
        button.btn-guardar { background: var(--morado-principal); color: var(--blanco); border: 1px solid var(--blanco); padding: 10px 15px; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button.btn-guardar:hover { background: var(--morado-oscuro); }
        a { text-decoration: none; color: var(--morado-claro); }
        a:hover { color: var(--blanco); }
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
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/procesos/pro_alumnos.php">Alumnos</a></li>
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

    <h2 class="mt-3">Alumnos</h2>

    <?php if ($editar): ?>
        <h3>Editar Alumno</h3>
        <form method="post" class="form-edicion">
            <input type="hidden" name="editar" value="1">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)($editar[$col_id] ?? ""), ENT_QUOTES, "UTF-8") ?>">

            Nombre: <input type="text" name="nombre" value="<?= htmlspecialchars((string)($editar["nombre"] ?? ""), ENT_QUOTES, "UTF-8") ?>" required>
            Apellido Paterno: <input type="text" name="apaterno" value="<?= htmlspecialchars((string)($editar["apaterno"] ?? ""), ENT_QUOTES, "UTF-8") ?>" required>
            Apellido Materno: <input type="text" name="amaterno" value="<?= htmlspecialchars((string)($editar["amaterno"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
            Domicilio: <input type="text" name="dom" value="<?= htmlspecialchars((string)($editar["dom"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
            Correo: <input type="email" name="mail" value="<?= htmlspecialchars((string)($editar["mail"] ?? ""), ENT_QUOTES, "UTF-8") ?>">
            Telefono: <input type="text" name="tel" value="<?= htmlspecialchars((string)($editar["tel"] ?? ""), ENT_QUOTES, "UTF-8") ?>">

            Grupo:
            <select name="id_grupo" required>
                <?php foreach ($grupos as $opt): ?>
                    <?php
                    $grp = (string)($opt["grupo"] ?? "");
                    $sel = ($grp === (string)($editar["id_grupo"] ?? "")) ? "selected" : "";
                    ?>
                    <option value="<?= htmlspecialchars($grp, ENT_QUOTES, "UTF-8") ?>" <?= $sel ?>><?= htmlspecialchars($grp, ENT_QUOTES, "UTF-8") ?></option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn-guardar">Guardar Cambios</button>
            <a href="/proyecto-AABR/Escuela/procesos/pro_alumnos.php" style="margin-left: 10px;">Cancelar</a>
        </form>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre Completo</th>
            <th>Correo</th>
            <th>Telefono</th>
            <th>Grupo</th>
            <th>Estado</th>
            <th style="width: 120px; text-align: center;">Acciones</th>
        </tr>
        <?php foreach ($alumnos as $a): ?>
            <?php $id_actual = (string)($a[$col_id] ?? ""); ?>
            <tr>
                <td><?= htmlspecialchars($id_actual, ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars(trim(($a["apaterno"] ?? "") . " " . ($a["amaterno"] ?? "") . ", " . ($a["nombre"] ?? "")), ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)($a["mail"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)($a["tel"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                <td><?= htmlspecialchars((string)($a["id_grupo"] ?? ""), ENT_QUOTES, "UTF-8") ?></td>
                <td><span class="badge-activo">Activo</span></td>
                <td style="text-align: center; white-space: nowrap;">
                    <a href="/proyecto-AABR/Escuela/registro/reg_alumno.php" class="btn-act btn-add" title="Anadir / Nuevo"><i class="fa fa-plus"></i></a>
                    <a href="?edit=<?= urlencode($id_actual) ?>" class="btn-act btn-edit" title="Editar"><i class="fa fa-pen"></i></a>
                    <a href="?del=<?= urlencode($id_actual) ?>" class="btn-act btn-del" title="Dar de baja" onclick="return confirm('Dar de baja a este alumno?')"><i class="fa fa-trash"></i></a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>