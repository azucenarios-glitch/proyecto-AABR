<?php
require_once __DIR__ . "/config/auth.php";
requerirLogin();
$nombreMostrar = htmlspecialchars($_SESSION['nombre_mostrar'] ?? $_SESSION['nombre_user']);
$puestoDesc    = htmlspecialchars($_SESSION['puesto_desc'] ?? $_SESSION['rol']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Escolar - Inicio</title>
    <link href="<?= $base ?>/assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-main: #090512;
            --bg-panel: #130924;
            --bg-card: #1a0d30;
            --border-purple: #6f2dbd;
            --border-subtle: #3b1c6e;
            --accent-purple: #7c3aed;
            --accent-purple-hover: #6d28d9;
            --text-main: #f3e8ff;
            --text-muted: #c4b5fd;
        }

        body.dark-school-theme {
            background-color: var(--bg-main);
            color: var(--text-main);
            min-height: 100vh;
        }

        .dark-school-theme .navbar,
        .dark-school-theme .dropdown-menu {
            background-color: var(--bg-panel) !important;
            border-color: var(--border-subtle) !important;
        }

        .dark-school-theme .nav-link,
        .dark-school-theme .navbar-brand,
        .dark-school-theme .dropdown-item {
            color: var(--text-main) !important;
        }

        .dark-school-theme .dropdown-item:hover,
        .dark-school-theme .nav-link.active {
            background-color: var(--accent-purple) !important;
            color: #ffffff !important;
        }

        .dashboard-shell {
            background-color: var(--bg-panel);
            border: 1.5px solid var(--border-purple);
            border-radius: 14px;
            padding: 2rem;
            box-shadow: 0 0 28px rgba(111, 45, 189, 0.22);
        }

        .dashboard-header {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
            margin-bottom: 1.75rem;
        }

        .dashboard-title {
            font-size: 2.1rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.35rem;
            letter-spacing: -0.01em;
        }

        .dashboard-subtitle {
            color: var(--text-muted);
            margin-bottom: 0;
            font-size: 0.98rem;
        }

        .user-meta {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .welcome-text {
            color: var(--text-main);
            font-size: 0.92rem;
        }

        .role-badge {
            background-color: var(--bg-card);
            color: var(--text-main);
            border: 1px solid var(--border-subtle);
            font-weight: 500;
            padding: 0.35rem 0.7rem;
            border-radius: 6px;
            font-size: 0.82rem;
        }

        .module-card {
            background-color: var(--bg-card);
            border: 1px solid var(--border-subtle);
            border-radius: 10px;
            transition: border-color 0.2s ease, transform 0.2s ease;
        }

        .module-card:hover {
            border-color: var(--accent-purple);
            transform: translateY(-2px);
        }

        .module-card .card-body {
            padding: 1.35rem;
            display: flex;
            flex-direction: column;
        }

        .module-card .card-title {
            color: #ffffff;
            font-weight: 600;
            font-size: 1.15rem;
            margin-bottom: 0.5rem;
        }

        .module-card .card-text {
            color: var(--text-muted);
            font-size: 0.92rem;
            line-height: 1.45;
            margin-bottom: 1rem;
        }

        .btn-purple {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
            color: #ffffff;
            font-weight: 500;
        }

        .btn-purple:hover,
        .btn-purple:focus {
            background-color: var(--accent-purple-hover);
            border-color: var(--accent-purple-hover);
            color: #ffffff;
        }

        .btn-purple-outline {
            background-color: transparent;
            border-color: var(--accent-purple);
            color: #ddd6fe;
            font-weight: 500;
        }

        .btn-purple-outline:hover,
        .btn-purple-outline:focus {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
            color: #ffffff;
        }

        .btn-purple-dark {
            background-color: #2e1065;
            border-color: #6d28d9;
            color: #f3e8ff;
            font-weight: 500;
        }

        .btn-purple-dark:hover,
        .btn-purple-dark:focus {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
            color: #ffffff;
        }
    </style>
</head>
<body class="dark-school-theme">
<?php include __DIR__ . "/navbar/navbar.php"; ?>

<div class="container my-4">
    <?php if (isset($_GET['denegado'])) { ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <strong>Acceso denegado:</strong> tu perfil (<?= $puestoDesc ?>) no tiene permisos para entrar a ese módulo.
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"></button>
        </div>
    <?php } ?>

    <div class="dashboard-shell">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Administración</h1>
                <p class="dashboard-subtitle">
                    Bienvenido, <strong><?= $nombreMostrar ?></strong> | Jerarquía: <?= $puestoDesc ?>
                </p>
            </div>
            <div class="user-meta">
                <span class="welcome-text">Bienvenido, <strong><?= $nombreMostrar ?></strong></span>
                <span class="role-badge">Jerarquía: <?= $puestoDesc ?></span>
            </div>
        </div>

        <div class="row g-3">
            <?php if (esAdmin()) { ?>
                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Registros</h5>
                            <p class="card-text">Catálogos de alumnos, profesores, grupos, materias, unidades y usuarios.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= $base ?>/cruds/cru_alumnos.php" class="btn btn-purple btn-sm">Alumnos</a>
                                <a href="<?= $base ?>/cruds/cru_profesores.php" class="btn btn-purple btn-sm">Profesores</a>
                                <a href="<?= $base ?>/cruds/cru_grupos.php" class="btn btn-purple btn-sm">Grupos</a>
                                <a href="<?= $base ?>/cruds/cru_materias.php" class="btn btn-purple btn-sm">Materias</a>
                                <a href="<?= $base ?>/cruds/cru_unidades.php" class="btn btn-purple btn-sm">Unidades</a>
                                <a href="<?= $base ?>/cruds/cru_usuarios.php" class="btn btn-purple-dark btn-sm">Usuarios</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Procesos</h5>
                            <p class="card-text">Inscripción de alumnos a grupos y asignación de materias</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= $base ?>/formularios/procesos/inscripcion_grupos.php" class="btn btn-purple">Inscripción</a>
                                <a href="<?= $base ?>/formularios/procesos/asignacion_materias.php" class="btn btn-purple-outline">Asignación</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Calificaciones</h5>
                            <p class="card-text">Captura individual y masiva por unidad, con corrección.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= $base ?>/formularios/procesos/captura_calificaciones.php" class="btn btn-purple">Capturar</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Reportes</h5>
                            <p class="card-text">Alumnos por grupo, materias por alumno, calificaciones por materia y grupo, y boleta.</p>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="<?= $base ?>/formularios/reportes/rep_alu-gru.php" class="btn btn-purple btn-sm">Alumnos por grupo</a>
                                <a href="<?= $base ?>/formularios/reportes/rep_mat-alu.php" class="btn btn-purple btn-sm">Materias por alumno</a>
                                <a href="<?= $base ?>/formularios/reportes/rep_calificaciones.php" class="btn btn-purple btn-sm">Calificaciones</a>
                                <a href="<?= $base ?>/formularios/reportes/rep_boleta.php" class="btn btn-purple btn-sm">Boleta</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } elseif (esMaestro()) { ?>
                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Mis Grupos</h5>
                            <p class="card-text">Consulta la lista de alumnos inscritos en los grupos donde impartes clase.</p>
                            <div>
                                <a href="<?= $base ?>/formularios/reportes/rep_alu-gru.php" class="btn btn-purple">Ver mis grupos</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Calificaciones</h5>
                            <p class="card-text">Captura individual y masiva por unidad para tus materias y alumnos asignados.</p>
                            <div>
                                <a href="<?= $base ?>/formularios/procesos/captura_calificaciones.php" class="btn btn-purple">Capturar calificaciones</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Reporte por Grupo</h5>
                            <p class="card-text">Consulta y descarga en Excel las calificaciones de tus materias y grupos.</p>
                            <div>
                                <a href="<?= $base ?>/formularios/reportes/rep_calificaciones.php" class="btn btn-purple">Ver calificaciones</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } elseif (esAlumno()) { ?>
                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Meter Materias</h5>
                            <p class="card-text">Selecciona tus materias y profesores para inscribirlas en tu carga académica.</p>
                            <div>
                                <a href="<?= $base ?>/formularios/procesos/asignacion_materias.php" class="btn btn-purple">Inscribir materias</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card module-card">
                        <div class="card-body">
                            <h5 class="card-title">Mis Calificaciones</h5>
                            <p class="card-text">Consulta tus calificaciones por unidad, promedios por materia y boleta oficial.</p>
                            <div>
                                <a href="<?= $base ?>/formularios/reportes/rep_boleta.php" class="btn btn-purple">Ver mi boleta</a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>