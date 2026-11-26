<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

function obtener_esquema_reg_grp(PDO $pdo, string $tabla): array {
    $cols = [];
    $pk = null;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}`");
        if ($stmt) {
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $cols[] = $row["Field"];
                if ($row["Key"] === "PRI" && $pk === null) {
                    $pk = $row["Field"];
                }
            }
        }
    } catch (PDOException $e) {
        return ["cols" => [], "pk" => null];
    }
    return ["cols" => $cols, "pk" => $pk];
}

function detectar_col_reg_grp(array $cols, array $candidatos, $default = null) {
    foreach ($candidatos as $c) {
        if (in_array($c, $cols, true)) {
            return $c;
        }
    }
    return $default;
}

$conexion->exec("CREATE TABLE IF NOT EXISTS grupos (
    id_grupo INT AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$esq_grp  = obtener_esquema_reg_grp($conexion, "grupos");
$cols_grp = $esq_grp["cols"];

if (count($cols_grp) === 0) {
    die("No se pudo leer la estructura de la tabla grupos.");
}

$pk_grp  = detectar_col_reg_grp($cols_grp, ["id_grupo", "id", "cve_grupo", "clave"], $esq_grp["pk"] ?? $cols_grp[0]);
$col_grp = detectar_col_reg_grp($cols_grp, ["grupo", "nombre", "descripcion", "nom_grupo"]);

if ($col_grp === null) {
    foreach ($cols_grp as $c) {
        if ($c !== $pk_grp) {
            $col_grp = $c;
            break;
        }
    }
}

$mensaje  = "";
$tipo_msg = "success";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $grupo = trim((string)($_POST["grupo"] ?? ""));
    if ($grupo !== "" && $col_grp !== null) {
        $stmt = $conexion->prepare("INSERT INTO grupos (`{$col_grp}`) VALUES (?)");
        $stmt->execute([$grupo]);
        $mensaje  = "Grupo registrado correctamente.";
        $tipo_msg = "success";
    } else {
        $mensaje  = "Ingresa el nombre del grupo.";
        $tipo_msg = "danger";
    }
}

$grupos = $conexion->query("SELECT * FROM grupos ORDER BY `{$pk_grp}` DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro - Grupo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2, h3 { color: var(--morado-claro); margin-top: 20px; }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .card-form { background: var(--negro-panel); border: 1px solid var(--borde-morado); border-radius: 6px; padding: 20px; max-width: 600px; margin-top: 15px; }
        .form-label { color: var(--morado-claro); font-weight: bold; }
        .form-control { background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); }
        .btn-morado { background: var(--morado-principal); color: var(--blanco); border: 1px solid var(--blanco); padding: 9px 16px; border-radius: 4px; font-weight: bold; }
        .btn-morado:hover { background: var(--morado-oscuro); color: var(--blanco); }
        .alert { background: var(--negro-panel); color: var(--blanco); border: 1px solid var(--morado-principal); max-width: 600px; }
        table { border-collapse: collapse; width: 100%; max-width: 600px; background: var(--negro-panel); color: var(--blanco); margin-top: 15px; }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
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
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/registro/reg_grupos.php">Grupo</a></li>
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

    <h2>Registro de Grupo</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_msg, ENT_QUOTES, "UTF-8") ?> mt-3"><?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <div class="card-form">
        <form method="post" action="/proyecto-AABR/Escuela/registro/reg_grupos.php" class="row g-3">
            <div class="col-md-8">
                <label class="form-label" for="grupo">Nombre del Grupo</label>
                <input type="text" name="grupo" id="grupo" class="form-control" placeholder="Ej. 1A" required>
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <button type="submit" class="btn-morado w-100">Guardar Grupo</button>
            </div>
        </form>
    </div>

    <h3>Grupos Registrados</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Grupo</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($grupos) > 0): ?>
                <?php foreach ($grupos as $g): ?>
                    <?php
                    $id_val  = (string)($g[$pk_grp] ?? "");
                    $grp_val = ($col_grp !== null && isset($g[$col_grp])) ? (string)$g[$col_grp] : $id_val;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($id_val, ENT_QUOTES, "UTF-8") ?></td>
                        <td><?= htmlspecialchars($grp_val, ENT_QUOTES, "UTF-8") ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">No hay grupos registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>