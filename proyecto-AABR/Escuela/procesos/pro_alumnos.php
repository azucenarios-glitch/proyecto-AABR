<?php
$servidor = "localhost";
$usuario  = "root";
$clave    = "";
$base     = "Escuela";

// Nombre real de la columna ID en tu tabla 'alumnos' (ajústalo según phpMyAdmin)
$col_id = "id_alumno";

// 1. Conexión inicial
$conexion = mysqli_connect($servidor, $usuario, $clave);
if (!$conexion) {
    die("Conexión fallida: " . mysqli_connect_error());
}

// 2. Crear la base de datos automáticamente si no existe
$sql_db = "CREATE DATABASE IF NOT EXISTS `$base` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci";
mysqli_query($conexion, $sql_db);

// 3. Seleccionar la base de datos
mysqli_select_db($conexion, $base);

// Crear tablas si no existen
mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS grupos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    grupo VARCHAR(50) NOT NULL
)");

mysqli_query($conexion, "CREATE TABLE IF NOT EXISTS alumnos (
    $col_id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL,
    apaterno VARCHAR(50) NOT NULL,
    amaterno VARCHAR(50),
    dom VARCHAR(100),
    mail VARCHAR(100),
    tel VARCHAR(20),
    id_grupo VARCHAR(50),
    estatus INT DEFAULT 1
)");

// Comprobación de seguridad para la columna 'estatus'
$check_column = mysqli_query($conexion, "SHOW COLUMNS FROM alumnos LIKE 'estatus'");
if (mysqli_num_rows($check_column) == 0) {
    mysqli_query($conexion, "ALTER TABLE alumnos ADD estatus INT DEFAULT 1");
}

// Dar de baja lógica (cambiar estatus a 0)
if (isset($_GET['del'])) {
    $id_del = (int)$_GET['del'];
    mysqli_query($conexion, "UPDATE alumnos SET estatus = 0 WHERE `$col_id` = $id_del");
    header("Location: pro_alumnos.php");
    exit;
}

// Guardar edición
if ($_POST && isset($_POST['editar'])) {
    $id       = (int)$_POST['id'];
    $nombre   = mysqli_real_escape_string($conexion, $_POST['nombre']);
    $apaterno = mysqli_real_escape_string($conexion, $_POST['apaterno']);
    $amaterno = mysqli_real_escape_string($conexion, $_POST['amaterno']);
    $dom      = mysqli_real_escape_string($conexion, $_POST['dom']);
    $mail     = mysqli_real_escape_string($conexion, $_POST['mail']);
    $tel      = mysqli_real_escape_string($conexion, $_POST['tel']);
    $id_grupo = mysqli_real_escape_string($conexion, $_POST['id_grupo']);

    $sql = "UPDATE alumnos
            SET nombre='$nombre', apaterno='$apaterno', amaterno='$amaterno',
                dom='$dom', mail='$mail', tel='$tel', id_grupo='$id_grupo'
            WHERE `$col_id` = $id";
    mysqli_query($conexion, $sql);
    header("Location: pro_alumnos.php");
    exit;
}

// Cargar registro para editar
$editar = null;
if (isset($_GET['edit'])) {
    $id_edit = (int)$_GET['edit'];
    $res = mysqli_query($conexion, "SELECT * FROM alumnos WHERE `$col_id` = $id_edit");
    $editar = mysqli_fetch_assoc($res);
}

// Consulta de alumnos activos
$alumnos = mysqli_query($conexion, "SELECT * FROM alumnos WHERE estatus = 1 ORDER BY apaterno");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Procesos Alumnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background: #fff;
            color: #333;
        }

        h2, h3 {
            color: #6610f2;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            margin: 15px 0;
        }

        th {
            background: #FF69B4;
            color: #fff;
            padding: 10px;
            text-align: left;
        }

        td {
            border: 1px solid #f1d4e3;
            padding: 10px;
            vertical-align: middle;
        }

        tr:nth-child(even) {
            background: #fff8fb;
        }

        .btn-act {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            color: #fff !important;
            border-radius: 4px;
            text-decoration: none !important;
            font-size: 13px;
            margin-right: 3px;
        }

        .btn-add  { background-color: #27ae60; }
        .btn-edit { background-color: #0000FF; }
        .btn-del  { background-color: #dc3545; }

        form {
            background: #fff;
            padding: 15px;
            border: 2px solid #FF69B4;
            max-width: 500px;
            margin-bottom: 20px;
        }

        input, select {
            width: 100%;
            padding: 8px;
            margin: 5px 0 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        button {
            background: #3d0a91;
            color: #fff;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
        }

        a {
            text-decoration: none;
            color: #FF69B4;
        }
    </style>
</head>
<body class="p-3 m-0 border-0">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link active" href="../index.php">Inicio</a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Baja</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../Baja/ba_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="../Baja/ba_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../Baja/ba_profesores.php">Profesor</a></li>
                <li><a class="dropdown-item" href="../Baja/ba_alumnos.php">Alumnos</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Procesos</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../procesos/pro_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_materias.php">Materias</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_capcal.php">Capcal</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_asignat.php">Asignat</a></li>
                <li><a class="dropdown-item" href="../procesos/pro_insgrup.php">Insgrup</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Registro</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../registro/reg_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../registro/reg_profesores.php">Profesores</a></li>
                <li><a class="dropdown-item" href="../registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item" href="../registro/reg_alumno.php">Alumno</a></li>
                <li><a class="dropdown-item" href="../registro/reg_unidad.php">Unidad</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Reporte</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../reporter/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_grupos.php">Grupos</a></li>
            </ul>
        </li>
    </ul>

    <h2 class="mt-3">Alumnos</h2>

    <?php if ($editar): ?>
        <h3>Editar Alumno</h3>
        <form method="post">
            <input type="hidden" name="editar" value="1">
            <input type="hidden" name="id" value="<?= htmlspecialchars((string)($editar[$col_id] ?? ''), ENT_QUOTES) ?>">

            Nombre: <input type="text" name="nombre" value="<?= htmlspecialchars($editar['nombre'] ?? '', ENT_QUOTES) ?>" required>
            Apellido Paterno: <input type="text" name="apaterno" value="<?= htmlspecialchars($editar['apaterno'] ?? '', ENT_QUOTES) ?>" required>
            Apellido Materno: <input type="text" name="amaterno" value="<?= htmlspecialchars($editar['amaterno'] ?? '', ENT_QUOTES) ?>">
            Domicilio: <input type="text" name="dom" value="<?= htmlspecialchars($editar['dom'] ?? '', ENT_QUOTES) ?>">
            Correo: <input type="email" name="mail" value="<?= htmlspecialchars($editar['mail'] ?? '', ENT_QUOTES) ?>">
            Teléfono: <input type="text" name="tel" value="<?= htmlspecialchars($editar['tel'] ?? '', ENT_QUOTES) ?>">

            Grupo:
            <select name="id_grupo" required>
                <?php
                $g = mysqli_query($conexion, "SELECT DISTINCT grupo FROM grupos ORDER BY grupo");
                while ($opt = mysqli_fetch_assoc($g)) {
                    $sel = ($opt['grupo'] == ($editar['id_grupo'] ?? '')) ? "selected" : "";
                    $grp = htmlspecialchars($opt['grupo'], ENT_QUOTES);
                    echo "<option value='$grp' $sel>$grp</option>";
                }
                ?>
            </select>

            <button type="submit">Guardar Cambios</button>
            <a href="pro_alumnos.php" style="margin-left: 10px;">Cancelar</a>
        </form>
    <?php endif; ?>

    <table>
        <tr>
            <th>ID</th>
            <th>Nombre Completo</th>
            <th>Correo</th>
            <th>Teléfono</th>
            <th>Grupo</th>
            <th>Estado</th>
            <th style="width: 120px; text-align: center;">Acciones</th>
        </tr>
        <?php while ($a = mysqli_fetch_assoc($alumnos)): ?>
            <?php $id_actual = $a[$col_id] ?? ''; ?>
            <tr>
                <td><?= htmlspecialchars((string)$id_actual) ?></td>
                <td><?= htmlspecialchars(($a['apaterno'] ?? '') . ' ' . ($a['amaterno'] ?? '') . ', ' . ($a['nombre'] ?? '')) ?></td>
                <td><?= htmlspecialchars($a['mail'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['tel'] ?? '') ?></td>
                <td><?= htmlspecialchars($a['id_grupo'] ?? '') ?></td>
                <td><strong>Activo</strong></td>
                <td style="text-align: center; white-space: nowrap;">
                    <a href="../registro/reg_alumno.php" class="btn-act btn-add" title="Añadir / Nuevo"><i class="fa fa-plus"></i></a>
                    <a href="?edit=<?= urlencode((string)$id_actual) ?>" class="btn-act btn-edit" title="Editar"><i class="fa fa-pen"></i></a>
                    <a href="?del=<?= urlencode((string)$id_actual) ?>" class="btn-act btn-del" title="Dar de baja" onclick="return confirm('¿Dar de baja a este alumno?')"><i class="fa fa-trash"></i></a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</body>
</html>