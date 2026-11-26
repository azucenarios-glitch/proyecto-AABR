<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

$conexion->exec("CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(50) NOT NULL
)");

$conexion->exec("CREATE TABLE IF NOT EXISTS alumnos (
    id_alumno INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apaterno VARCHAR(50) NOT NULL,
    amaterno VARCHAR(50),
    dom VARCHAR(100),
    mail VARCHAR(100),
    tel VARCHAR(20),
    id_grupo VARCHAR(50),
    estatus INT DEFAULT 1
)");

$mensaje = "";
$tipo_msg = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nombre   = trim((string)($_POST["nombre"] ?? ""));
    $apaterno = trim((string)($_POST["apaterno"] ?? ""));
    $amaterno = trim((string)($_POST["amaterno"] ?? ""));
    $dom      = trim((string)($_POST["dom"] ?? ""));
    $mail     = trim((string)($_POST["mail"] ?? ""));
    $tel      = trim((string)($_POST["tel"] ?? ""));
    $id_grupo = trim((string)($_POST["id_grupo"] ?? ""));

    if ($nombre !== "" && $apaterno !== "") {
        $stmt = $conexion->prepare("
            INSERT INTO alumnos (nombre, apaterno, amaterno, dom, mail, tel, id_grupo, estatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        $stmt->execute([$nombre, $apaterno, $amaterno, $dom, $mail, $tel, $id_grupo]);
        $mensaje = "Alumno registrado correctamente.";
        $tipo_msg = "success";
    } else {
        $mensaje = "Completa al menos nombre y apellido paterno.";
        $tipo_msg = "danger";
    }
}

$grupos = $conexion->query("SELECT DISTINCT grupo FROM grupos ORDER BY grupo")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro - Alumno</title>
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
        .card-form { background: var(--negro-panel); border: 1px solid var(--borde-morado); border-radius: 6px; padding: 20px; max-width: 720px; margin-top: 15px; }
        .form-label { color: var(--morado-claro); font-weight: bold; }
        .form-control, .form-select { background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); }
        .btn-morado { background: var(--morado-principal); color: var(--blanco); border: 1px solid var(--blanco); padding: 9px 16px; border-radius: 4px; font-weight: bold; }
        .btn-morado:hover { background: var(--morado-oscuro); color: var(--blanco); }
        .alert { background: var(--negro-panel); color: var(--blanco); border: 1px solid var(--morado-principal); max-width: 720px; }
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
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button">Registro</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/registro/reg_alumno.php">Alumno</a></li>
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

    <h2>Registro de Alumno</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_msg, ENT_QUOTES, "UTF-8") ?> mt-3"><?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="card-form">
        <form method="post" action="/proyecto-AABR/Escuela/registro/reg_alumno.php" class="row g-3">
            <div class="col-md-4">
                <label class="form-label" for="nombre">Nombre</label>
                <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="apaterno">Apellido Paterno</label>
                <input type="text" name="apaterno" id="apaterno" class="form-control" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="amaterno">Apellido Materno</label>
                <input type="text" name="amaterno" id="amaterno" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="dom">Domicilio</label>
                <input type="text" name="dom" id="dom" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="mail">Correo</label>
                <input type="email" name="mail" id="mail" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="tel">Telefono</label>
                <input type="text" name="tel" id="tel" class="form-control">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="id_grupo">Grupo</label>
                <select name="id_grupo" id="id_grupo" class="form-select">
                    <option value="">Selecciona un grupo...</option>
                    <?php foreach ($grupos as $g): ?>
                        <?php $grp = (string)($g["grupo"] ?? ""); ?>
                        <?php if ($grp !== ""): ?>
                            <option value="<?= htmlspecialchars($grp, ENT_QUOTES, "UTF-8") ?>"><?= htmlspecialchars($grp, ENT_QUOTES, "UTF-8") ?></option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12 d-flex gap-2">
                <button type="submit" class="btn-morado">Guardar Alumno</button>
                <a href="/proyecto-AABR/Escuela/procesos/pro_alumnos.php" class="btn btn-outline-light">Ver Lista</a>
            </div>
        </form>
    </div>
</body>
</html>