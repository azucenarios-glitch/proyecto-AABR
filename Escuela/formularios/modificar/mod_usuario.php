<?php
require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR']);

$mensaje = "";
$error = "";
$usuarioEdit = null;

$puestos = $conexion->query("SELECT ID_PUESTO, DESCRIPCION_PUESTO FROM puesto WHERE STATUS_PUESTO = 'ALTA' ORDER BY ID_PUESTO")->fetch_all(MYSQLI_ASSOC);
$profesores = $conexion->query("SELECT ID_PROFESORES, CLAVE, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM profesores WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);
$alumnos = $conexion->query("SELECT ID_ALUMNOS, MATRICULA, CONCAT(NOMBRE, ' ', APELLIDO_PATERNO, ' ', APELLIDO_MATERNO) AS NOMBRE_COMPLETO FROM alumnos WHERE ESTATUS = 'ALTA' ORDER BY APELLIDO_PATERNO, NOMBRE")->fetch_all(MYSQLI_ASSOC);

if (isset($_POST["actualizar"])) {
    $id            = (int) ($_POST["id"] ?? 0);
    $nombreUsuario = trim($_POST["nombre_usuario"] ?? "");
    $nuevaPass     = $_POST["password_usuario"] ?? "";
    $puestoId      = (int) ($_POST["puesto"] ?? 0);
    $idProfesor    = !empty($_POST["id_profesor"]) ? (int) $_POST["id_profesor"] : null;
    $idAlumno      = !empty($_POST["id_alumno"]) ? (int) $_POST["id_alumno"] : null;
    $estatus       = $_POST["estatus"] ?? "ALTA";

    if ($puestoId === 1) {
        $idProfesor = null;
        $idAlumno   = null;
    } elseif ($puestoId === 2) {
        $idAlumno = null;
        if ($idProfesor === null) {
            $error = "Debes seleccionar el profesor vinculado.";
        }
    } elseif ($puestoId === 3) {
        $idProfesor = null;
        if ($idAlumno === null) {
            $error = "Debes seleccionar el alumno vinculado.";
        }
    }

    if ($error === "") {
        try {
            if ($nuevaPass !== "") {
                $hash = sha1($nuevaPass);
                $sql = "UPDATE usuarios SET NOMBREUSER = ?, PWUSER = ?, PUESTO = ?, ID_PROFESORES = ?, ID_ALUMNOS = ?, STATUS_USER = ? WHERE ID_USER = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("ssiiisi", $nombreUsuario, $hash, $puestoId, $idProfesor, $idAlumno, $estatus, $id);
            } else {
                $sql = "UPDATE usuarios SET NOMBREUSER = ?, PUESTO = ?, ID_PROFESORES = ?, ID_ALUMNOS = ?, STATUS_USER = ? WHERE ID_USER = ?";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param("siiisi", $nombreUsuario, $puestoId, $idProfesor, $idAlumno, $estatus, $id);
            }
            $stmt->execute();
            $stmt->close();
            $mensaje = "Usuario actualizado correctamente.";
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $error = "Ese nombre de usuario o persona vinculada ya está registrado en otra cuenta.";
            } else {
                $error = "Ocurrió un error al actualizar: " . $e->getMessage();
            }
        }
    }
}

if (isset($_GET["id"])) {
    $id = (int) $_GET["id"];
    $stmt = $conexion->prepare("SELECT * FROM usuarios WHERE ID_USER = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $usuarioEdit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$listaUsuarios = null;
if ($usuarioEdit === null) {
    $listaUsuarios = $conexion->query(
        "SELECT u.ID_USER, u.NOMBREUSER, p.DESCRIPCION_PUESTO, u.STATUS_USER
         FROM usuarios u
         INNER JOIN puesto p ON p.ID_PUESTO = u.PUESTO
         ORDER BY u.ID_USER DESC"
    );
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Usuarios</title>
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

        .btn-blue {
            background-color: var(--accent-blue);
            border-color: var(--accent-blue);
            color: #ffffff;
            font-weight: 500;
        }

        .btn-blue:hover,
        .btn-blue:focus {
            background-color: var(--accent-blue-hover);
            border-color: var(--accent-blue-hover);
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

        .table-dark-purple {
            --bs-table-bg: var(--bg-card);
            --bs-table-striped-bg: #21113d;
            --bs-table-hover-bg: #29154b;
            --bs-table-border-color: var(--border-subtle);
            --bs-table-color: var(--text-main);
            color: var(--text-main);
            border-color: var(--border-subtle);
        }

        .table-dark-purple thead th {
            background-color: #2e1065;
            color: #ffffff;
            border-color: var(--border-purple);
            font-weight: 600;
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
        <div class="col-lg-9">
            <div class="card form-shell">
                <div class="form-header">
                    <h2>Modificar Usuarios</h2>
                </div>

                <div class="form-body">
                    <?php if ($mensaje !== "") { ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
                    <?php } ?>

                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php } ?>

                    <?php if ($usuarioEdit !== null) { ?>

                        <form method="POST">
                            <input type="hidden" name="id" value="<?= (int) $usuarioEdit["ID_USER"] ?>">

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label">Nombre de usuario</label>
                                    <input type="text" name="nombre_usuario" class="form-control" maxlength="50" required
                                           value="<?= htmlspecialchars($usuarioEdit["NOMBREUSER"]) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nueva contraseña (en blanco para conservar)</label>
                                    <input type="password" name="password_usuario" class="form-control" minlength="4">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-8">
                                    <label class="form-label">Puesto / Jerarquía</label>
                                    <select name="puesto" id="selectPuesto" class="form-select" required onchange="actualizarVinculo()">
                                        <?php foreach ($puestos as $p) { ?>
                                            <option value="<?= (int) $p["ID_PUESTO"] ?>" <?= ((int) $usuarioEdit["PUESTO"] === (int) $p["ID_PUESTO"]) ? "selected" : "" ?>>
                                                <?= htmlspecialchars($p["ID_PUESTO"] . " - " . $p["DESCRIPCION_PUESTO"]) ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Estatus</label>
                                    <select name="estatus" class="form-select">
                                        <option value="ALTA" <?= $usuarioEdit["STATUS_USER"] === "ALTA" ? "selected" : "" ?>>ALTA</option>
                                        <option value="BAJA" <?= $usuarioEdit["STATUS_USER"] === "BAJA" ? "selected" : "" ?>>BAJA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 <?= ((int) $usuarioEdit["PUESTO"] === 2) ? "" : "d-none" ?>" id="bloqueProfesor">
                                <label class="form-label">Profesor vinculado</label>
                                <select name="id_profesor" id="selectProfesor" class="form-select">
                                    <option value="">Selecciona un profesor...</option>
                                    <?php foreach ($profesores as $pr) { ?>
                                        <option value="<?= (int) $pr["ID_PROFESORES"] ?>" <?= ((int) ($usuarioEdit["ID_PROFESORES"] ?? 0) === (int) $pr["ID_PROFESORES"]) ? "selected" : "" ?>>
                                            <?= htmlspecialchars($pr["CLAVE"] . " - " . $pr["NOMBRE_COMPLETO"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="mb-4 <?= ((int) $usuarioEdit["PUESTO"] === 3) ? "" : "d-none" ?>" id="bloqueAlumno">
                                <label class="form-label">Alumno vinculado</label>
                                <select name="id_alumno" id="selectAlumno" class="form-select">
                                    <option value="">Selecciona un alumno...</option>
                                    <?php foreach ($alumnos as $al) { ?>
                                        <option value="<?= (int) $al["ID_ALUMNOS"] ?>" <?= ((int) ($usuarioEdit["ID_ALUMNOS"] ?? 0) === (int) $al["ID_ALUMNOS"]) ? "selected" : "" ?>>
                                            <?= htmlspecialchars($al["MATRICULA"] . " - " . $al["NOMBRE_COMPLETO"]) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" name="actualizar" class="btn btn-purple">Guardar cambios</button>
                                <a href="<?= $base ?>/cruds/cru_usuarios.php" class="btn btn-purple-outline">Cancelar</a>
                            </div>
                        </form>

                    <?php } else { ?>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-dark-purple align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Puesto</th>
                                        <th>Estatus</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($listaUsuarios && $listaUsuarios->num_rows > 0) { ?>
                                        <?php while ($fila = $listaUsuarios->fetch_assoc()) { ?>
                                            <tr>
                                                <td><?= (int) $fila["ID_USER"] ?></td>
                                                <td><?= htmlspecialchars($fila["NOMBREUSER"]) ?></td>
                                                <td><?= htmlspecialchars($fila["DESCRIPCION_PUESTO"]) ?></td>
                                                <td><?= htmlspecialchars($fila["STATUS_USER"]) ?></td>
                                                <td><a href="?id=<?= (int) $fila["ID_USER"] ?>" class="btn btn-sm btn-blue">Editar</a></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-3" style="color: var(--text-muted);">No hay usuarios registrados todavía.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                    <?php } ?>

                    <div class="text-center mt-4 form-links">
                        <a href="<?= $base ?>/formularios/formulario/for_usuario.php">Registrar nuevo usuario</a>
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