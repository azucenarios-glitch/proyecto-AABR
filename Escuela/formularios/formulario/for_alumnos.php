<?php

require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR']);

// Variable para saber si se guardaron los datos
$mostrarDatos = false;
$error = "";

// Comprobar si se presionó el botón
if (isset($_POST["guardar"])) {

    // Obtener los datos del formulario
    $matricula       = trim($_POST["matricula"] ?? "");
    $nombre          = trim($_POST["nombre"] ?? "");
    $apellidoPaterno = trim($_POST["apellidoPaterno"] ?? "");
    $apellidoMaterno = trim($_POST["apellidoMaterno"] ?? "");
    $domicilio       = trim($_POST["domicilio"] ?? "");
    $correo          = trim($_POST["correo"] ?? "");
    $telefono        = trim($_POST["telefono"] ?? "");

    // Insertar en la base de datos usando sentencia preparada
    $sql = "INSERT INTO alumnos (MATRICULA, NOMBRE, APELLIDO_PATERNO, APELLIDO_MATERNO, DOMICILIO, CORREO, TELEFONO)
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sssssss", $matricula, $nombre, $apellidoPaterno, $apellidoMaterno, $domicilio, $correo, $telefono);

    if ($stmt->execute()) {
        $mostrarDatos = true;
    } else {
        $error = "Ocurrió un error al guardar: " . $conexion->error;
    }

    $stmt->close();
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Alumnos</title>
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
            --accent-blue: #2563eb;
            --accent-blue-hover: #1d4ed8;
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

        .form-shell {
            background-color: var(--bg-panel);
            border: 1.5px solid var(--border-purple);
            border-radius: 14px;
            box-shadow: 0 0 28px rgba(111, 45, 189, 0.22);
            overflow: hidden;
        }

        .form-header {
            background-color: var(--bg-card);
            border-bottom: 1px solid var(--border-subtle);
            color: #ffffff;
            text-align: center;
            padding: 1.25rem 1rem;
        }

        .form-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 1.75rem;
        }

        .form-body {
            padding: 1.75rem;
        }

        .form-label {
            color: var(--text-main);
            font-size: 0.92rem;
        }

        .form-control {
            background-color: var(--bg-main);
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
        }

        .form-control:focus {
            background-color: var(--bg-main);
            border-color: var(--accent-purple);
            color: #ffffff;
            box-shadow: 0 0 0 0.2rem rgba(124, 58, 237, 0.25);
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

        .table-dark-purple {
            --bs-table-bg: var(--bg-card);
            --bs-table-border-color: var(--border-subtle);
            --bs-table-color: var(--text-main);
            color: var(--text-main);
            border-color: var(--border-subtle);
        }

        .table-dark-purple th {
            background-color: #2e1065;
            color: #ffffff;
            border-color: var(--border-purple);
            width: 32%;
        }

        .form-links a {
            color: #c4b5fd;
            text-decoration: none;
        }

        .form-links a:hover {
            color: #ffffff;
            text-decoration: underline;
        }
    </style>
</head>
<body class="dark-school-theme">
<?php include __DIR__ . "/../../navbar/navbar.php"; ?>

<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card form-shell">
                <div class="form-header">
                    <h2>Registro de Alumnos</h2>
                </div>

                <div class="form-body">
                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php } ?>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Matrícula</label>
                            <input type="text" name="matricula" class="form-control" required>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label">Nombre</label>
                                <input type="text" name="nombre" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Apellido paterno</label>
                                <input type="text" name="apellidoPaterno" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Apellido materno</label>
                                <input type="text" name="apellidoMaterno" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Domicilio</label>
                            <input type="text" name="domicilio" class="form-control" required>
                        </div>

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Correo electrónico</label>
                                <input type="email" name="correo" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="tel" name="telefono" class="form-control" required>
                            </div>
                        </div>

                        <button type="submit" name="guardar" class="btn btn-purple">
                            Registrar alumno
                        </button>
                    </form>

                    <?php if ($mostrarDatos === true) { ?>
                        <hr style="border-color: var(--border-subtle);">

                        <div class="alert alert-success mt-3">
                            Alumno guardado correctamente en la base de datos.
                        </div>

                        <h4 class="mb-3" style="color: #ffffff;">Datos del alumno</h4>

                        <div class="table-responsive">
                            <table class="table table-bordered table-dark-purple mb-0">
                                <tr>
                                    <th>Matrícula</th>
                                    <td><?= htmlspecialchars($matricula) ?></td>
                                </tr>
                                <tr>
                                    <th>Nombre</th>
                                    <td><?= htmlspecialchars($nombre) ?></td>
                                </tr>
                                <tr>
                                    <th>Apellido paterno</th>
                                    <td><?= htmlspecialchars($apellidoPaterno) ?></td>
                                </tr>
                                <tr>
                                    <th>Apellido materno</th>
                                    <td><?= htmlspecialchars($apellidoMaterno) ?></td>
                                </tr>
                                <tr>
                                    <th>Domicilio</th>
                                    <td><?= htmlspecialchars($domicilio) ?></td>
                                </tr>
                                <tr>
                                    <th>Correo electrónico</th>
                                    <td><?= htmlspecialchars($correo) ?></td>
                                </tr>
                                <tr>
                                    <th>Teléfono</th>
                                    <td><?= htmlspecialchars($telefono) ?></td>
                                </tr>
                            </table>
                        </div>
                    <?php } ?>

                    <div class="text-center mt-4 form-links">
                        <a href="<?= $base ?>/formularios/modificar/mod_alumnos.php">Modificar un alumno existente</a>
                        <span class="mx-2">|</span>
                        <a href="<?= $base ?>/cruds/cru_alumnos.php">Ir al catálogo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>