<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

$conexion->exec("CREATE TABLE IF NOT EXISTS profesores (
    id_prof INT AUTO_INCREMENT PRIMARY KEY,
    nom_prof VARCHAR(100) NOT NULL,
    apaterno_prof VARCHAR(100) NOT NULL,
    amaterno_prof VARCHAR(100) NOT NULL
)");

$conexion->exec("CREATE TABLE IF NOT EXISTS materias (
    id_mat INT AUTO_INCREMENT PRIMARY KEY,
    descripcion VARCHAR(150) NOT NULL,
    id_prof INT NOT NULL,
    FOREIGN KEY (id_prof) REFERENCES profesores(id_prof)
)");

if (isset($_GET["del"])) {
    $id_del = (int)$_GET["del"];
    if ($id_del > 0) {
        $stmt = $conexion->prepare("DELETE FROM materias WHERE id_mat = ?");
        $stmt->execute([$id_del]);
    }
    header("Location: /proyecto-AABR/Escuela/procesos/pro_materias.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["editar"])) {
    $id_mat      = (int)($_POST["id_mat"] ?? 0);
    $descripcion = trim((string)($_POST["descripcion"] ?? ""));
    $id_prof     = (int)($_POST["id_prof"] ?? 0);
    if ($id_mat > 0 && $descripcion !== "" && $id_prof > 0) {
        $stmt = $conexion->prepare("UPDATE materias SET descripcion = ?, id_prof = ? WHERE id_mat = ?");
        $stmt->execute([$descripcion, $id_prof, $id_mat]);
    }
    header("Location: /proyecto-AABR/Escuela/procesos/pro_materias.php");
    exit;
}

$editar = null;
if (isset($_GET["edit"])) {
    $id_edit = (int)$_GET["edit"];
    if ($id_edit > 0) {
        $stmt = $conexion->prepare("SELECT * FROM materias WHERE id_mat = ? LIMIT 1");
        $stmt->execute([$id_edit]);
        $editar = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

$profesores = $conexion->query("SELECT * FROM profesores ORDER BY apaterno_prof, amaterno_prof, nom_prof")->fetchAll(PDO::FETCH_ASSOC);
$materias = $conexion->query("
    SELECT m.*, CONCAT(p.apaterno_prof, ' ', p.amaterno_prof, ', ', p.nom_prof) AS profesor
    FROM materias m
    LEFT JOIN profesores p ON m.id_prof = p.id_prof
    ORDER BY m.id_mat ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos Materias</title>
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
        form.form-edicion { background: var(--negro-panel); color: var(--blanco); padding: 15px; border: 2px solid var(--morado-principal); border-radius: 6px; max-width: 520px; margin-bottom: 20px; }
        form.form-edicion input, form.form-edicion select { width: 100%; padding: 8px; margin: 5px 0 12px; background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); border-radius: 4px; }
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
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/procesos/pro_materias.php">Materias</a></li>
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

    <h2 class="mt-3">Procesos - Materias</h2>

    <?php if ($editar): ?>
        <h3>Editar Materia</h3>
        <form method="post" class="form-edicion">
            <input type="hidden" name="editar" value="1">
            <input type="hidden" name="id_mat" value="<?= (int)$editar["id_mat"] ?>">
            Materia:
            <input type="text" name="descripcion" value="<?= htmlspecialchars((string)$editar["descripcion"], ENT_QUOTES, "UTF-8") ?>" required>
            Profesor:
            <select name="id_prof" required>
                <?php foreach ($profesores as $p): ?>
                    <?php
                    $id_p = (int)$p["id_prof"];
                    $nom_p = trim($p["apaterno_prof"] . " " . $p["amaterno_prof"] . ", " . $p["nom_prof"]);
                    $sel = ($id_p === (int)$editar["id_prof"]) ? "selected" : "";
                    ?>
                    <option value="<?= $id_p ?>" <?= $sel ?>><?= htmlspecialchars($nom_p, ENT_QUOTES, "UTF-8") ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-guardar">Guardar Cambios</button>
            <a href="/proyecto-AABR/Escuela/procesos/pro_materias.php" style="margin-left: 10px;">Cancelar</a>
        </form>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Materia</th>
                <th>Profesor</th>
                <th style="width: 120px; text-align: center;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materias as $m): ?>
                <tr>
                    <td><?= (int)$m["id_mat"] ?></td>
                    <td><?= htmlspecialchars((string)$m["descripcion"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars((string)($m["profesor"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                    <td style="text-align: center; white-space: nowrap;">
                        <a href="/proyecto-AABR/Escuela/registro/reg_materias.php" class="btn-act btn-add" title="Nuevo"><i class="fa fa-plus"></i></a>
                        <a href="?edit=<?= (int)$m["id_mat"] ?>" class="btn-act btn-edit" title="Editar"><i class="fa fa-pen"></i></a>
                        <a href="?del=<?= (int)$m["id_mat"] ?>" class="btn-act btn-del" title="Eliminar" onclick="return confirm('Eliminar esta materia?')"><i class="fa fa-trash"></i></a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>