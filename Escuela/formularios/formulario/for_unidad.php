<?php
require_once __DIR__ . "/../../config/conexion.php";
requerirRol(['ADMINISTRADOR']);

$mostrarDatos = false;
$error = "";

if (isset($_POST["guardar"])) {
    $idUnidad = (int) ($_POST["id_unidad"] ?? 0);

    try {
        $sql = "INSERT INTO unidad (ID_UNIDAD) VALUES (?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $idUnidad);
        $stmt->execute();
        $stmt->close();
        $mostrarDatos = true;
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error = "Ya existe una unidad con ese Id.";
        } else {
            $error = "Ocurrió un error al guardar: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro de Unidades</title>
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
                    <h2>Registro de Unidades</h2>
                </div>

                <div class="form-body">
                    <?php if ($error !== "") { ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?></div>
                    <?php } ?>

                    <form method="POST">
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Id de unidad</label>
                                <input type="number" name="id_unidad" class="form-control" min="1" required>
                            </div>
                        </div>

                        <button type="submit" name="guardar" class="btn btn-purple">Registrar unidad</button>
                    </form>

                    <?php if ($mostrarDatos) { ?>
                        <hr style="border-color: var(--border-subtle);">

                        <div class="alert alert-success mt-3">
                            Unidad guardada correctamente en la base de datos.
                        </div>

                        <h4 class="mb-3" style="color: #ffffff;">Información de la unidad</h4>

                        <div class="table-responsive">
                            <table class="table table-bordered table-dark-purple mb-0">
                                <tr><th>Id de unidad</th><td><?= (int) $idUnidad ?></td></tr>
                            </table>
                        </div>
                    <?php } ?>

                    <div class="text-center mt-4 form-links">
                        <a href="<?= $base ?>/formularios/modificar/mod_unidad.php">Modificar una unidad existente</a>
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