<?php
$servidor = "localhost";
$usuario  = "root";
$clave    = "";
$base     = "escuela";

$conexion = mysqli_connect($servidor, $usuario, $clave, $base);
if (!$conexion) {
    die("Conexión fallida: " . mysqli_connect_error());
}
mysqli_set_charset($conexion, "utf8mb4");

$mensaje     = "";
$tipo_alerta = "success";

// Registrar nuevo puesto (opcional, desde el mismo módulo)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"]) && $_POST["accion"] === "reg_puesto") {
    $nombre_puesto = isset($_POST["nombre_puesto"]) ? trim($_POST["nombre_puesto"]) : "";

    if ($nombre_puesto !== "") {
        $stmt_p = mysqli_prepare($conexion, "INSERT INTO puestos (nombre, estatus) VALUES (?, 1)");
        mysqli_stmt_bind_param($stmt_p, "s", $nombre_puesto);
        if (mysqli_stmt_execute($stmt_p)) {
            $mensaje     = "Puesto registrado correctamente.";
            $tipo_alerta = "success";
        } else {
            $mensaje     = "Error al registrar el puesto: " . mysqli_error($conexion);
            $tipo_alerta = "danger";
        }
        mysqli_stmt_close($stmt_p);
    } else {
        $mensaje     = "Ingresa el nombre del puesto.";
        $tipo_alerta = "danger";
    }
}

// Registrar nuevo usuario con encriptación sha1 (o md5)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["accion"]) && $_POST["accion"] === "reg_usuario") {
    $user_login = isset($_POST["usuario"]) ? trim($_POST["usuario"]) : "";
    $pass_raw   = isset($_POST["password"]) ? $_POST["password"] : "";
    $nombre_usr = isset($_POST["nombre"]) ? trim($_POST["nombre"]) : "";
    $id_puesto  = isset($_POST["id_puesto"]) ? (int)$_POST["id_puesto"] : 0;

    if ($user_login !== "" && $pass_raw !== "" && $nombre_usr !== "" && $id_puesto > 0) {
        // Encriptación con sha1 (si tu clase usó md5, cambia sha1 por md5)
        $pass_hash = sha1($pass_raw);

        $stmt_u = mysqli_prepare(
            $conexion,
            "INSERT INTO usuarios (usuario, password, nombre, id_puesto, estatus) VALUES (?, ?, ?, ?, 1)"
        );
        mysqli_stmt_bind_param($stmt_u, "sssi", $user_login, $pass_hash, $nombre_usr, $id_puesto);

        if (mysqli_stmt_execute($stmt_u)) {
            $mensaje     = "Usuario registrado correctamente.";
            $tipo_alerta = "success";
        } else {
            $mensaje     = "Error al registrar usuario (verifica que el nombre de usuario no esté repetido): " . mysqli_error($conexion);
            $tipo_alerta = "danger";
        }
        mysqli_stmt_close($stmt_u);
    } else {
        $mensaje     = "Completa todos los campos del usuario y selecciona un puesto.";
        $tipo_alerta = "danger";
    }
}

// Consultar puestos disponibles
$puestos_res = mysqli_query($conexion, "SELECT id_puesto, nombre FROM puestos WHERE estatus = 1 ORDER BY nombre ASC");
$lista_puestos = [];
if ($puestos_res) {
    while ($p = mysqli_fetch_assoc($puestos_res)) {
        $lista_puestos[] = $p;
    }
}

// Consultar usuarios registrados con su puesto
$usuarios_res = mysqli_query(
    $conexion,
    "SELECT u.id_usuario, u.usuario, u.nombre, u.estatus, p.nombre AS nombre_puesto
     FROM usuarios u
     LEFT JOIN puestos p ON u.id_puesto = p.id_puesto
     ORDER BY u.id_usuario DESC"
);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Registro — Usuarios y Puestos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #fff5f7; color: #333; }
        h2, h3 { color: #d63384; margin-top: 15px; }
        .card-box { background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.06); margin-top: 15px; }
    </style>
</head>
<body class="p-3 m-0">
    <ul class="nav nav-pills">
        <li class="nav-item">
            <a class="nav-link" href="../index.php">Inicio</a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Baja</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../baja/ba_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="../baja/ba_grupos.php">Grupos</a></li>
                <li><a class="dropdown-item" href="../baja/ba_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../baja/ba_profesores.php">Profesor</a></li>
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
            <a class="nav-link dropdown-toggle active" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Registro</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../registro/reg_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../registro/reg_profesores.php">Profesor</a></li>
                <li><a class="dropdown-item" href="../registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item" href="../registro/reg_alumno.php">Alumno</a></li>
                <li><a class="dropdown-item" href="../registro/reg_unidad.php">Unidad</a></li>
                <li><a class="dropdown-item active" href="../registro/reg_usuarios.php">Usuarios</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#" role="button" aria-expanded="false">Reporte</a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="../reportes/rep_general.php">Reportes Generales</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_alumnos.php">Alumnos</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_profesor.php">Profesores</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_materias.php">Materia</a></li>
                <li><a class="dropdown-item" href="../reportes/rep_grupos.php">Grupos</a></li>
            </ul>
        </li>
    </ul>

    <h2>Registro de Usuarios y Puestos</h2>

    <?php if ($mensaje !== ""): ?>
        <div class="alert alert-<?= htmlspecialchars($tipo_alerta, ENT_QUOTES, 'UTF-8') ?> mt-3" role="alert">
            <?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="row g-4 mt-1">
        <div class="col-md-7">
            <div class="card-box">
                <h3>Registrar Usuario</h3>
                <form method="POST" action="reg_usuarios.php" class="row g-3">
                    <input type="hidden" name="accion" value="reg_usuario" />

                    <div class="col-md-6">
                        <label for="usuario" class="form-label">Usuario (login)</label>
                        <input type="text" name="usuario" id="usuario" class="form-control" required />
                    </div>

                    <div class="col-md-6">
                        <label for="password" class="form-label">Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" required />
                    </div>

                    <div class="col-md-6">
                        <label for="nombre" class="form-label">Nombre completo</label>
                        <input type="text" name="nombre" id="nombre" class="form-control" required />
                    </div>

                    <div class="col-md-6">
                        <label for="id_puesto" class="form-label">Puesto</label>
                        <select name="id_puesto" id="id_puesto" class="form-select" required>
                            <option value="">Selecciona un puesto...</option>
                            <?php foreach ($lista_puestos as $p): ?>
                                <option value="<?= (int)$p["id_puesto"] ?>">
                                    <?= htmlspecialchars($p["nombre"], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">Guardar usuario</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="col-md-5">
            <div class="card-box">
                <h3>Registrar Puesto</h3>
                <form method="POST" action="reg_usuarios.php" class="row g-3">
                    <input type="hidden" name="accion" value="reg_puesto" />
                    <div class="col-12">
                        <label for="nombre_puesto" class="form-label">Nombre del puesto</label>
                        <input type="text" name="nombre_puesto" id="nombre_puesto" class="form-control" placeholder="Ej. Administrador" required />
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-secondary">Guardar puesto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-box">
        <h3>Usuarios Registrados</h3>
        <table class="table table-bordered table-striped">
            <thead class="table-dark">
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Puesto</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($usuarios_res && mysqli_num_rows($usuarios_res) > 0): ?>
                    <?php while ($u = mysqli_fetch_assoc($usuarios_res)): ?>
                        <tr>
                            <td><?= (int)$u["id_usuario"] ?></td>
                            <td><?= htmlspecialchars($u["usuario"], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u["nombre"], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($u["nombre_puesto"] ?? "Sin puesto", ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= ((int)$u["estatus"] === 1) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>' ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted">No hay usuarios registrados todavía.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>