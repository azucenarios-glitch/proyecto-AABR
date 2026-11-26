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

$mensaje = "";
$tipo_msg = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["asignar"])) {
    $id_mat  = (int)($_POST["id_mat"] ?? 0);
    $id_prof = (int)($_POST["id_prof"] ?? 0);
    if ($id_mat > 0 && $id_prof > 0) {
        $stmt = $conexion->prepare("UPDATE materias SET id_prof = ? WHERE id_mat = ?");
        $stmt->execute([$id_prof, $id_mat]);
        $mensaje = "Asignacion de profesor actualizada correctamente.";
        $tipo_msg = "success";
    } else {
        $mensaje = "Selecciona una materia y un profesor.";
        $tipo_msg = "danger";
    }
}

$profesores = $conexion->query("SELECT * FROM profesores ORDER BY apaterno_prof, amaterno_prof, nom_prof")->fetchAll(PDO::FETCH_ASSOC);
$materias = $conexion->query("
    SELECT m.*, CONCAT(p.apaterno_prof, ' ', p.amaterno_prof, ', ', p.nom_prof) AS profesor
    FROM materias m
    LEFT JOIN profesores p ON m.id_prof = p.id_prof
    ORDER BY m.descripcion ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos - Asignacion de Materias</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2, h3 { color: var(--morado-claro); margin-top: 20px; }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .card-form { background: var(--negro-panel); border: 1px solid var(--borde-morado); border-radius: 6px; padding: 20px; max-width: 720px; margin-top: 15px; }
        .form-label { color: var(--morado-claro); font-weight: bold; }
        .form-select { background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); }
        .btn-morado { background: var(--morado-principal); color: var(--blanco); border: 1px solid var(--blanco); padding: 9px 16px; border-radius: 4px; font-weight: bold; }
        .btn-morado:hover { background: var(--morado-oscuro); color: var(--blanco); }
        .alert { background: var(--negro-panel); color: var(--blanco); border: 1px solid var(--morado-principal); max-width: 720px; }
        table { border-collapse: collapse; width: 100%; background: var(--negro-panel); color: var(--blanco); margin-top: 15px; }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
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
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_materias.php">Materias</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/procesos/pro_asignat.php">Asignat</a></li>
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

    <h2>Asignacion de Profesor a Materia</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_msg, ENT_QUOTES, "UTF-8") ?> mt-3"><?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="card-form">
        <form method="post" action="/proyecto-AABR/Escuela/procesos/pro_asignat.php" class="row g-3">
            <div class="col-md-5">
                <label class="form-label" for="id_mat">Materia</label>
                <select name="id_mat" id="id_mat" class="form-select" required>
                    <option value="">Selecciona una materia...</option>
                    <?php foreach ($materias as $m): ?>
                        <option value="<?= (int)$m["id_mat"] ?>"><?= htmlspecialchars((string)$m["descripcion"], ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label" for="id_prof">Profesor</label>
                <select name="id_prof" id="id_prof" class="form-select" required>
                    <option value="">Selecciona un profesor...</option>
                    <?php foreach ($profesores as $p): ?>
                        <?php $nom_p = trim($p["apaterno_prof"] . " " . $p["amaterno_prof"] . ", " . $p["nom_prof"]); ?>
                        <option value="<?= (int)$p["id_prof"] ?>"><?= htmlspecialchars($nom_p, ENT_QUOTES, "UTF-8") ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" name="asignar" value="1" class="btn-morado w-100">Asignar</button>
            </div>
        </form>
    </div>

    <h3>Estado Actual de Asignaciones</h3>
    <table>
        <thead>
            <tr>
                <th>ID Materia</th>
                <th>Materia</th>
                <th>Profesor Asignado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($materias as $m): ?>
                <tr>
                    <td><?= (int)$m["id_mat"] ?></td>
                    <td><?= htmlspecialchars((string)$m["descripcion"], ENT_QUOTES, "UTF-8") ?></td>
                    <td><?= htmlspecialchars((string)($m["profesor"] ?? "-"), ENT_QUOTES, "UTF-8") ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>