<?php
session_start();
require_once __DIR__ . '/conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

$conteos = [
    "alumnos"  => 0,
    "materias" => 0,
    "capcal"   => 0,
    "usuarios" => 0,
];

foreach ($conteos as $tabla => $_) {
    try {
        $stmt = $conexion->query("SELECT COUNT(*) AS total FROM `{$tabla}`");
        $row = $stmt ? $stmt->fetch(PDO::FETCH_ASSOC) : null;
        if ($row && isset($row["total"])) {
            $conteos[$tabla] = (int)$row["total"];
        }
    } catch (PDOException $e) {
        $conteos[$tabla] = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sistema Escolar - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h1, h2, h3 { color: var(--morado-claro); }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .hero-box { background: var(--negro-panel); border: 2px solid var(--morado-principal); border-radius: 8px; padding: 22px; margin-top: 20px; }
        .grid-tarjetas { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-top: 18px; }
        .tarjeta-resumen { background: var(--negro-fila-alt); border: 1px solid var(--borde-morado); border-radius: 6px; padding: 16px; }
        .tarjeta-resumen .num { font-size: 1.8rem; font-weight: bold; color: var(--blanco); }
        .btn-acceso { background: var(--morado-principal); color: var(--blanco) !important; border: 1px solid var(--blanco); padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; display: inline-block; }
        .btn-acceso:hover { background: var(--morado-oscuro); }
    </style>
</head>
<body class="p-3 m-0 border-0">
    <ul class="nav nav-pills">
        <li class="nav-item"><a class="nav-link active" href="/proyecto-AABR/Escuela/index.php">Inicio</a></li>
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

    <div class="hero-box">
        <h1>Sistema Escolar - Panel Principal</h1>
        <p class="mb-3">Gestion de alumnos, materias, captura de calificaciones (capcal), vista de alumno y usuarios.</p>
        <div class="d-flex flex-wrap gap-2">
            <a href="/proyecto-AABR/Escuela/procesos/pro_capcal.php" class="btn-acceso">Capturar Calificaciones</a>
            <a href="/proyecto-AABR/Escuela/procesos/pro_vista.php" class="btn-acceso">Vista Calificaciones Alumno</a>
            <a href="/proyecto-AABR/Escuela/registro/reg_usuarios.php" class="btn-acceso">Administrar Usuarios</a>
            <a href="/proyecto-AABR/Escuela/reportes/rep_materias.php" class="btn-acceso">Reporte Materias</a>
        </div>

        <div class="grid-tarjetas">
            <div class="tarjeta-resumen">
                <div>Alumnos</div>
                <div class="num"><?= (int)$conteos["alumnos"] ?></div>
            </div>
            <div class="tarjeta-resumen">
                <div>Materias</div>
                <div class="num"><?= (int)$conteos["materias"] ?></div>
            </div>
            <div class="tarjeta-resumen">
                <div>Calificaciones (capcal)</div>
                <div class="num"><?= (int)$conteos["capcal"] ?></div>
            </div>
            <div class="tarjeta-resumen">
                <div>Usuarios</div>
                <div class="num"><?= (int)$conteos["usuarios"] ?></div>
            </div>
        </div>
    </div>
</body>
</html>