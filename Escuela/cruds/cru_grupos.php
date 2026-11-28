<?php
$mysqli = include "conexion.php";
requerirRol(['ADMINISTRADOR']);

// ---------- BAJA ----------
if (isset($_GET["baja"])) {
    $id = (int) $_GET["baja"];
    $stmt = $mysqli->prepare("UPDATE grupos SET ESTATUS = 'BAJA' WHERE ID_GRUPOS = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: cru_grupos.php");
    exit;
}

// ---------- LISTADO ----------
$resultado = $mysqli->query("SELECT ID_GRUPOS AS idgrupo, NOMBRE_GRUPO AS nombregrupo, TURNO AS turno, SEMESTRE AS semestre, DESCRIPCION AS descripcion FROM grupos WHERE ESTATUS = 'ALTA'");
$grupos = $resultado->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo de Grupos</title>
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

        .panel-shell {
            background-color: var(--bg-panel);
            border: 1.5px solid var(--border-purple);
            border-radius: 14px;
            padding: 2rem;
            box-shadow: 0 0 28px rgba(111, 45, 189, 0.22);
        }

        .page-title {
            color: #ffffff;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
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
    </style>
</head>
<body class="dark-school-theme">

<?php include __DIR__ . "/../navbar/navbar.php"; ?>

<div class="container my-4">
    <div class="panel-shell">
        <h1 class="page-title">Catálogo de Grupos</h1>

        <div class="d-flex justify-content-end mb-3">
            <a href="<?= $base ?>/formularios/formulario/for_grupos.php" class="btn btn-purple">
                Registrar nuevo grupo
            </a>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover table-dark-purple align-middle mb-0">
                <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Nombre del Grupo</th>
                        <th scope="col">Turno</th>
                        <th scope="col">Semestre</th>
                        <th scope="col">Descripción</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
<?php if (count($grupos) === 0) { ?>
                    <tr>
                        <td colspan="6" class="text-center py-3" style="color: var(--text-muted);">No hay grupos registrados.</td>
                    </tr>
<?php } ?>
<?php foreach ($grupos as $grupo) { ?>
                    <tr>
                        <th scope="row"><?= (int) $grupo["idgrupo"] ?></th>
                        <td><?= htmlspecialchars($grupo["nombregrupo"]) ?></td>
                        <td><?= htmlspecialchars($grupo["turno"]) ?></td>
                        <td><?= htmlspecialchars($grupo["semestre"]) ?></td>
                        <td><?= htmlspecialchars($grupo["descripcion"]) ?></td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                <a href="<?= $base ?>/formularios/modificar/mod_grupos.php?id=<?= (int) $grupo["idgrupo"] ?>" class="btn btn-sm btn-blue">
                                    Modificar
                                </a>
                                <a href="cru_grupos.php?baja=<?= (int) $grupo["idgrupo"] ?>" class="btn btn-sm btn-purple-outline" onclick="return confirm('¿Dar de baja este grupo?');">
                                    Baja
                                </a>
                            </div>
                        </td>
                    </tr>
<?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="<?= $base ?>/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>