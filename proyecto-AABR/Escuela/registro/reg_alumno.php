<?php
$servidor = "localhost";
$usuario  = "root";
$clave    = "";
$base     = "Escuela";

$conexion = mysqli_connect($servidor, $usuario, $clave, $base);
if (!$conexion) {
    die("Conexión: " . mysqli_connect_error());
}

$mensaje = "";
$ok = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $n  = trim($_POST['nombre'] ?? '');
    $a  = trim($_POST['apaterno'] ?? '');
    $am = trim($_POST['amaterno'] ?? '');
    $d  = trim($_POST['dom'] ?? '');
    $m  = trim($_POST['mail'] ?? '');
    $t  = trim($_POST['tel'] ?? '');
    $g  = trim($_POST['id_grupo'] ?? '');

    // Si tu llave primaria tiene otro nombre y NO es AUTO_INCREMENT, inclúyela aquí.
    // Si es AUTO_INCREMENT, omite la columna y MySQL la generará sola.
    $sql = "INSERT INTO alumnos (nombre, apaterno, amaterno, dom, mail, tel, id_grupo, estatus)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)";

    $stmt = mysqli_prepare($conexion, $sql);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "sssssss", $n, $a, $am, $d, $m, $t, $g);
        if (mysqli_stmt_execute($stmt)) {
            $mensaje = "Alumno registrado";
            $ok = true;
        } else {
            $mensaje = "Error: " . mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
    } else {
        $mensaje = "Error al preparar la consulta: " . mysqli_error($conexion);
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro Alumnos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body { font-family: Arial, sans-serif; margin: 30px; background: #fff5f7; color: #333; }
        form { max-width: 500px; background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 0 10px #f8d7da; border-top: 4px solid #d63384; margin-top: 20px; }
        input, select { width: 100%; padding: 8px; margin: 5px 0 15px; box-sizing: border-box; border: 1px solid #f1b0b7; border-radius: 4px; }
        button { padding: 10px 20px; background: #c2185b; color: #fff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background: #a61e4d; }
        .msg { padding: 10px; border-radius: 4px; font-weight: bold; max-width: 500px; }
        .ok { background: #d4edda; color: #155724; }
        .err { background: #f8d7da; color: #721c24; }
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
                <li><a class="dropdown-item" href="../procesos/pro_alumnos.php">Alumnos</a></li>
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
                <li><a class="dropdown-item" href="../registro/reg_unidad.php">Unidad</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Reporte</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../reportes/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_grupos.php">Grupos</a></li>
            </ul>
        </li>
    </ul>

    <h2 class="mt-3">Registro de Alumnos</h2>

    <?php if ($mensaje !== ""): ?>
        <p class="msg <?= $ok ? 'ok' : 'err' ?>"><?= htmlspecialchars($mensaje) ?></p>
    <?php endif; ?>

    <form method="post">
        Nombre: <input type="text" name="nombre" required>
        Apellido Paterno: <input type="text" name="apaterno" required>
        Apellido Materno: <input type="text" name="amaterno">
        Domicilio: <input type="text" name="dom">
        Correo: <input type="email" name="mail">
        Teléfono: <input type="text" name="tel">
        Grupo:
        <select name="id_grupo" required>
            <option value="">-- Selecciona --</option>
            <?php
            $res = mysqli_query($conexion, "SELECT DISTINCT grupo FROM grupos ORDER BY grupo");
            if ($res) {
                while ($g = mysqli_fetch_assoc($res)) {
                    $grupoVal = htmlspecialchars($g['grupo'], ENT_QUOTES, 'UTF-8');
                    echo "<option value='{$grupoVal}'>{$grupoVal}</option>";
                }
            }
            ?>
        </select>
        <button type="submit">Guardar Alumno</button>
    </form>
</body>
</html>