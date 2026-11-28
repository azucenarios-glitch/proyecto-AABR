<?php
require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR']);

$mostrarDatos = false;
$error = "";

$puestos = $conexion->query("SELECT ID_PUESTO, DESCRIPCION_PUESTO FROM puesto WHERE STATUS_PUESTO = 'ALTA' ORDER BY ID_PUESTO")->fetch_all(MYSQLI_ASSOC);
$profesores = $conexion->query("SELECT ID_PROFESORES, CLAVE, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM profesores WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);
$alumnos = $conexion->query("SELECT ID_ALUMNOS, MATRICULA, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM alumnos WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);

$nombreUsuario = "";
$puestoId = 0;
$puestoNombre = "";
$vinculoResumen = "Acceso general administrativo";

if (isset($_POST["guardar"])) {
    $nombreUsuario   = trim($_POST["nombre_usuario"] ?? "");
    $passwordUsuario = $_POST["password_usuario"] ?? "";
    $puestoId        = (int) ($_POST["puesto"] ?? 0);
    $idProfesor      = !empty($_POST["id_profesor"]) ? (int) $_POST["id_profesor"] : null;
    $idAlumno        = !empty($_POST["id_alumno"]) ? (int) $_POST["id_alumno"] : null;

    if ($puestoId === 1) {
        $idProfesor = null;
        $idAlumno   = null;
    } elseif ($puestoId === 2) {
        $idAlumno = null;
        if ($idProfesor === null) {
            $error = "Debes seleccionar el profesor al que pertenece este usuario.";
        }
    } elseif ($puestoId === 3) {
        $idProfesor = null;
        if ($idAlumno === null) {
            $error = "Debes seleccionar el alumno al que pertenece este usuario.";
        }
    }

    if ($error === "") {
        $hash = sha1($passwordUsuario);
        try {
            $sql = "INSERT INTO usuarios (NOMBREUSER, PWUSER, PUESTO, ID_PROFESORES, ID_ALUMNOS) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("ssiii", $nombreUsuario, $hash, $puestoId, $idProfesor, $idAlumno);
            $stmt->execute();
            $stmt->close();

            foreach ($puestos as $p) {
                if ((int) $p["ID_PUESTO"] === $puestoId) {
                    $puestoNombre = $p["DESCRIPCION_PUESTO"];
                    break;
                }
            }

            if ($idProfesor !== null) {
                foreach ($profesores as $pr) {
                    if ((int) $pr["ID_PROFESORES"] === $idProfesor) {
                        $vinculoResumen = "Profesor: " . $pr["CLAVE"] . " - " . $pr["NOMBRE_COMPLETO"];
                        break;
                    }
                }
            } elseif ($idAlumno !== null) {
                foreach ($alumnos as $al) {
                    if ((int) $al["ID_ALUMNOS"] === $idAlumno) {
                        $vinculoResumen = "Alumno: " . $al["MATRICULA"] . " - " . $al["NOMBRE_COMPLETO"];
                        break;
                    }
                }
            }

            $mostrarDatos = true;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $error = "Ese nombre de usuario o esa persona ya tiene una cuenta asignada.";
            } else {
                $error = "Ocurrió un error al guardar: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Usuarios</title>
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

        .form-control,
        .form-select {
            background-color: var(--bg-main);
            border: 1px solid var(--border-subtle);
            color: var(--text-main);
        }

        .form-control:focus,
        .form-select:focus {
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
                    <h2>Registro de Usuarios</h2>
                </div>

                <div class="form-body">
                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php } ?>

                    <form method="POST">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Nombre de usuario (login)</label>
                                <input type="text" name="nombre_usuario" class="form-control" maxlength="50" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Contraseña</label>
                                <input type="password" name="password_usuario" class="form-control" minlength="4" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Puesto / Jerarquía</label>
                            <select name="puesto" id="selectPuesto" class="form-select" required onchange="actualizarVinculo()">
                                <option value="">Selecciona un puesto...</option>
                                <?php foreach ($puestos as $p) { ?>
                                    <option value="<?= (int) $p["ID_PUESTO"] ?>">
                                        <?= htmlspecialchars($p["ID_PUESTO"] . " - " . $p["DESCRIPCION_PUESTO"]) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-3 d-none" id="bloqueProfesor">
                            <label class="form-label">Profesor vinculado</label>
                            <select name="id_profesor" id="selectProfesor" class="form-select">
                                <option value="">Selecciona un profesor...</option>
                                <?php foreach ($profesores as $pr) { ?>
                                    <option value="<?= (int) $pr["ID_PROFESORES"] ?>">
                                        <?= htmlspecialchars($pr["CLAVE"] . " - " . $pr["NOMBRE_COMPLETO"]) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="mb-4 d-none" id="bloqueAlumno">
                            <label class="form-label">Alumno vinculado</label>
                            <select name="id_alumno" id="selectAlumno" class="form-select">
                                <option value="">Selecciona un alumno...</option>
                                <?php foreach ($alumnos as $al) { ?>
                                    <option value="<?= (int) $al["ID_ALUMNOS"] ?>">
                                        <?= htmlspecialchars($al["MATRICULA"] . " - " . $al["NOMBRE_COMPLETO"]) ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <button type="submit" name="guardar" class="btn btn-purple">Registrar usuario</button>
                    </form>

                    <?php if ($mostrarDatos) { ?>
                        <hr style="border-color: var(--border-subtle);">

                        <div class="alert alert-success mt-3">
                            Usuario guardado correctamente en la base de datos.
                        </div>

                        <h4 class="mb-3" style="color: #ffffff;">Información del usuario</h4>

                        <div class="table-responsive">
                            <table class="table table-bordered table-dark-purple mb-0">
                                <tr><th>Nombre de usuario</th><td><?= htmlspecialchars($nombreUsuario) ?></td></tr>
                                <tr><th>Puesto</th><td><?= htmlspecialchars($puestoNombre) ?></td></tr>
                                <tr><th>Persona vinculada</th><td><?= htmlspecialchars($vinculoResumen) ?></td></tr>
                            </table>
                        </div>
                    <?php } ?>

                    <div class="text-center mt-4 form-links">
                        <a href="<?= $base ?>/formularios/modificar/mod_usuario.php">Modificar un usuario existente</a>
                        <span class="mx-2">|</span>
                        <a href="<?= $base ?>/cruds/cru_usuarios.php">Ir al catálogo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function actualizarVinculo() {
    const puesto = document.getElementById('selectPuesto').value;
    const bloqueProf = document.getElementById('bloqueProfesor');
    const bloqueAlum = document.getElementById('bloqueAlumno');
    const selProf = document.getElementById('selectProfesor');
    const selAlum = document.getElementById('selectAlumno');

    bloqueProf.classList.add('d-none');
    bloqueAlum.classList.add('d-none');
    selProf.required = false;
    selAlum.required = false;

    if (puesto === '2') {
        bloqueProf.classList.remove('d-none');
        selProf.required = true;
    } else if (puesto === '3') {
        bloqueAlum.classList.remove('d-none');
        selAlum.required = true;
    }
}
</script>
<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>