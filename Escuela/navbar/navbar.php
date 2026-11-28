<?php
require_once __DIR__ . "/../config/auth.php";

$navbarSolo = isset($_SERVER["SCRIPT_FILENAME"]) && realpath($_SERVER["SCRIPT_FILENAME"]) === realpath(__FILE__);
$scriptActual = basename($_SERVER["PHP_SELF"] ?? "");

if ($navbarSolo) {
    requerirLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Navbar - Escuela</title>
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

        .navbar-purple {
            background-color: var(--bg-main) !important;
            border-bottom: 1px solid var(--border-subtle);
            padding-top: 0.7rem;
            padding-bottom: 0.7rem;
        }

        .navbar-purple .nav-btn {
            background-color: var(--bg-panel);
            color: #ffffff !important;
            border: 1px solid var(--border-subtle);
            border-radius: 7px;
            padding: 0.42rem 0.9rem;
            font-size: 0.92rem;
            font-weight: 500;
            text-decoration: none;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .navbar-purple .nav-btn:hover,
        .navbar-purple .nav-btn:focus,
        .navbar-purple .nav-btn.show {
            background-color: var(--bg-card);
            border-color: var(--border-purple);
            color: #ffffff !important;
        }

        .navbar-purple .nav-btn.active {
            background-color: var(--accent-purple) !important;
            border-color: var(--accent-purple) !important;
            color: #ffffff !important;
            font-weight: 600;
        }

        .navbar-purple .dropdown-menu {
            background-color: var(--bg-panel) !important;
            border: 1px solid var(--border-purple) !important;
            border-radius: 8px;
            margin-top: 0.35rem;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.65);
        }

        .navbar-purple .dropdown-item {
            color: var(--text-main) !important;
            font-size: 0.9rem;
            padding: 0.45rem 0.95rem;
        }

        .navbar-purple .dropdown-item:hover,
        .navbar-purple .dropdown-item:focus {
            background-color: var(--accent-purple) !important;
            color: #ffffff !important;
        }

        .navbar-purple .dropdown-divider {
            border-color: var(--border-subtle) !important;
        }

        .navbar-purple .navbar-toggler {
            border-color: var(--border-purple);
        }

        .badge-rol-purple {
            background-color: var(--bg-panel);
            color: var(--text-main);
            border: 1px solid var(--border-purple);
            font-weight: 600;
        }

        .btn-salir-purple {
            background-color: var(--bg-panel);
            border: 1px solid var(--border-subtle);
            color: #ffffff;
            font-weight: 500;
        }

        .btn-salir-purple:hover,
        .btn-salir-purple:focus {
            background-color: var(--accent-purple-hover);
            border-color: var(--accent-purple-hover);
            color: #ffffff;
        }
    </style>
</head>
<body class="dark-school-theme">
<?php } ?>
<nav class="navbar navbar-expand-lg navbar-dark navbar-purple">
  <div class="container-fluid">

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuPrincipal" aria-controls="menuPrincipal" aria-expanded="false" aria-label="Alternar navegación">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="menuPrincipal">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-2 flex-wrap align-items-lg-center">

        <li class="nav-item">
          <a class="nav-link nav-btn <?= $scriptActual === 'principal.php' ? 'active' : '' ?>" href="<?= $base ?>/principal.php">Inicio</a>
        </li>

        <?php if (esAdmin()) { ?>
          <li class="nav-item dropdown">
            <a class="nav-link nav-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Registros</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= $base ?>/cruds/cru_alumnos.php">Alumnos</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/cruds/cru_profesores.php">Profesores</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/cruds/cru_grupos.php">Grupos</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/cruds/cru_materias.php">Materias</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/cruds/cru_unidades.php">Unidades</a></li>
              <li><hr class="dropdown-divider"></li>
              <li><a class="dropdown-item" href="<?= $base ?>/cruds/cru_usuarios.php">Usuarios</a></li>
            </ul>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link nav-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Procesos</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= $base ?>/formularios/procesos/inscripcion_grupo.php">Inscripción a grupos</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/formularios/procesos/asignacion_materias.php">Asignación de materias</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/formularios/procesos/captura_calificaciones.php">Captura de calificaciones</a></li>
            </ul>
          </li>

          <li class="nav-item dropdown">
            <a class="nav-link nav-btn dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">Reportes</a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= $base ?>/formularios/reportes/rep_alu-gru.php">Alumnos por grupo</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/formularios/reportes/rep_calificaciones.php">Calificaciones por materia y grupo</a></li>
              <li><a class="dropdown-item" href="<?= $base ?>/formularios/reportes/rep_boleta.php">Boleta de calificaciones</a></li>
            </ul>
          </li>
        <?php } elseif (esMaestro()) { ?>
          <li class="nav-item">
            <a class="nav-link nav-btn <?= $scriptActual === 'rep_alu-gru.php' ? 'active' : '' ?>" href="<?= $base ?>/formularios/reportes/rep_alu-gru.php">Mis grupos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link nav-btn <?= $scriptActual === 'captura_calificaciones.php' ? 'active' : '' ?>" href="<?= $base ?>/formularios/procesos/captura_calificaciones.php">Capturar calificaciones</a>
          </li>
          <li class="nav-item">
            <a class="nav-link nav-btn <?= $scriptActual === 'rep_calificaciones.php' ? 'active' : '' ?>" href="<?= $base ?>/formularios/reportes/rep_calificaciones.php">Reporte de calificaciones</a>
          </li>
        <?php } elseif (esAlumno()) { ?>
          <li class="nav-item">
            <a class="nav-link nav-btn <?= $scriptActual === 'asignacion_materias.php' ? 'active' : '' ?>" href="<?= $base ?>/formularios/procesos/asignacion_materias.php">Meter materias</a>
          </li>
          <li class="nav-item">
            <a class="nav-link nav-btn <?= $scriptActual === 'rep_boleta.php' ? 'active' : '' ?>" href="<?= $base ?>/formularios/reportes/rep_boleta.php">Mis calificaciones</a>
          </li>
        <?php } ?>

      </ul>

      <?php if (isset($_SESSION['id_user'])) { ?>
        <div class="d-flex flex-wrap align-items-center gap-2">
          <span class="small" style="color: var(--text-main);">
            <?= htmlspecialchars($_SESSION['nombre_mostrar'] ?? $_SESSION['nombre_user']) ?>
          </span>
          <span class="badge badge-rol-purple"><?= htmlspecialchars($_SESSION['puesto_desc'] ?? $_SESSION['rol']) ?></span>
          <a href="<?= $base ?>/logout.php" class="btn btn-salir-purple btn-sm">Salir</a>
        </div>
      <?php } ?>
    </div>

  </div>
</nav>
<?php if ($navbarSolo) { ?>
<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php } ?>