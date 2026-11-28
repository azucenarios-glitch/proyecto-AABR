<?php

require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR']);

$mensaje = "";
$error = "";
$unidad = null;

if (isset($_POST["actualizar"])) {

    $id          = (int) ($_POST["id"] ?? 0);
    $descripcion = trim($_POST["descripcion"] ?? "");
    $estatus     = $_POST["estatus"] ?? "ALTA";

    try {
        $sql = "UPDATE unidad SET DESCRIPCION = ?, ESTATUS = ? WHERE ID_UNIDAD = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssi", $descripcion, $estatus, $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Unidad actualizada correctamente.";
    } catch (mysqli_sql_exception $e) {
        $error = "Ocurrió un error al actualizar: " . $e->getMessage();
    }
}

if (isset($_GET["id"])) {

    $id = (int) $_GET["id"];

    $stmt = $conexion->prepare("SELECT * FROM unidad WHERE ID_UNIDAD = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $unidad = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$listaUnidades = null;

if ($unidad === null) {
    $listaUnidades = $conexion->query("SELECT * FROM unidad ORDER BY ID_UNIDAD");
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modificar Unidades</title>
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

        .form-control[readonly] {
            background-color: var(--bg-card);
            color: var(--text-muted);
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
                    <h2>Modificar Unidades</h2>
                </div>

                <div class="form-body">
                    <?php if ($mensaje !== "") { ?>
                        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
                    <?php } ?>

                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php } ?>

                    <?php if ($unidad !== null) { ?>

                        <form method="POST">
                            <input type="hidden" name="id" value="<?= (int) $unidad["ID_UNIDAD"] ?>">

                            <div class="row g-3 mb-3">
                                <div class="col-md-4">
                                    <label class="form-label">Id de unidad</label>
                                    <input type="text" class="form-control" value="<?= (int) $unidad["ID_UNIDAD"] ?>" readonly>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label">Estatus</label>
                                    <select name="estatus" class="form-select">
                                        <option value="ALTA" <?= $unidad["ESTATUS"] === "ALTA" ? "selected" : "" ?>>ALTA</option>
                                        <option value="BAJA" <?= $unidad["ESTATUS"] === "BAJA" ? "selected" : "" ?>>BAJA</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Descripción de la unidad</label>
                                <textarea name="descripcion" class="form-control" rows="3" maxlength="255" required><?= htmlspecialchars($unidad["DESCRIPCION"]) ?></textarea>
                            </div>

                            <div class="d-flex flex-wrap gap-2">
                                <button type="submit" name="actualizar" class="btn btn-purple">Guardar cambios</button>
                                <a href="<?= $base ?>/cruds/cru_unidades.php" class="btn btn-purple-outline">Cancelar</a>
                            </div>
                        </form>

                    <?php } else { ?>

                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-dark-purple align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Descripción</th>
                                        <th>Estatus</th>
                                        <th>Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($listaUnidades && $listaUnidades->num_rows > 0) { ?>
                                        <?php while ($fila = $listaUnidades->fetch_assoc()) { ?>
                                            <tr>
                                                <td><?= (int) $fila["ID_UNIDAD"] ?></td>
                                                <td><?= htmlspecialchars($fila["DESCRIPCION"]) ?></td>
                                                <td><?= htmlspecialchars($fila["ESTATUS"]) ?></td>
                                                <td><a href="?id=<?= (int) $fila["ID_UNIDAD"] ?>" class="btn btn-sm btn-blue">Editar</a></td>
                                            </tr>
                                        <?php } ?>
                                    <?php } else { ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-3" style="color: var(--text-muted);">No hay unidades registradas todavía.</td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>

                    <?php } ?>

                    <div class="text-center mt-4 form-links">
                        <a href="<?= $base ?>/formularios/formulario/for_unidad.php">Registrar nueva unidad</a>
                        <span class="mx-2">|</span>
                        <a href="<?= $base ?>/cruds/cru_unidades.php">Ir al catálogo</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>