<?php
session_start();
require_once __DIR__ . '/../conexion.php';

if (!isset($conexion) || !($conexion instanceof PDO)) {
    die("No se pudo inicializar la conexion PDO desde conexion.php.");
}

function obtener_esquema_usr(PDO $pdo, string $tabla): array {
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

function detectar_col_usr(array $cols, array $candidatos, $default = null) {
    foreach ($candidatos as $c) {
        if (in_array($c, $cols, true)) {
            return $c;
        }
    }
    return $default;
}

$conexion->exec("
    CREATE TABLE IF NOT EXISTS usuarios (
        id_usuario INT AUTO_INCREMENT PRIMARY KEY,
        nombre_completo VARCHAR(120) DEFAULT '',
        usuario VARCHAR(80) NOT NULL,
        puesto VARCHAR(50) DEFAULT 'Alumnos',
        password_hash VARCHAR(255) NOT NULL DEFAULT '',
        puede_ingresar TINYINT(1) NOT NULL DEFAULT 1,
        estatus VARCHAR(20) NOT NULL DEFAULT 'Activo'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$esq_usr   = obtener_esquema_usr($conexion, "usuarios");
$cols_usr  = $esq_usr["cols"];
$pk_usr    = detectar_col_usr($cols_usr, ["id_usuario", "id"], $esq_usr["pk"] ?? ($cols_usr[0] ?? "id_usuario"));
$col_nom   = detectar_col_usr($cols_usr, ["nombre_completo", "nombre"]);
$col_user  = detectar_col_usr($cols_usr, ["usuario", "user", "login", "correo"]);
$col_puesto= detectar_col_usr($cols_usr, ["puesto", "rol", "tipo"]);
$col_pass  = detectar_col_usr($cols_usr, ["password_hash", "password", "clave"]);
$col_ing   = detectar_col_usr($cols_usr, ["puede_ingresar", "acceso"]);
$col_est   = detectar_col_usr($cols_usr, ["estatus", "estado"]);

$mensaje = "";
$tipo_msg = "info";

// Crear nuevo usuario desde formulario modal/panel si se solicita ?nuevo=1 o POST crear_usuario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["crear_usuario"])) {
    $nom_nuevo  = trim((string)($_POST["nombre_completo"] ?? ""));
    $user_nuevo = trim((string)($_POST["usuario"] ?? ""));
    $puesto_n   = trim((string)($_POST["puesto"] ?? "Alumnos"));
    $pass_n     = trim((string)($_POST["password"] ?? ""));
    $ing_n      = isset($_POST["puede_ingresar"]) ? 1 : 0;
    $est_n      = ($_POST["estatus"] ?? "Activo") === "Inactivo" ? "Inactivo" : "Activo";

    if ($user_nuevo !== "" && $pass_n !== "") {
        $campos = [];
        $vals   = [];
        $params = [];

        if ($col_nom) { $campos[] = "`{$col_nom}`"; $vals[] = "?"; $params[] = $nom_nuevo; }
        if ($col_user){ $campos[] = "`{$col_user}`"; $vals[] = "?"; $params[] = $user_nuevo; }
        if ($col_puesto){ $campos[] = "`{$col_puesto}`"; $vals[] = "?"; $params[] = $puesto_n; }
        if ($col_pass) {
            $campos[] = "`{$col_pass}`";
            $vals[] = "?";
            $params[] = password_hash($pass_n, PASSWORD_DEFAULT);
        }
        if ($col_ing) { $campos[] = "`{$col_ing}`"; $vals[] = "?"; $params[] = $ing_n; }
        if ($col_est) { $campos[] = "`{$col_est}`"; $vals[] = "?"; $params[] = $est_n; }

        if (count($campos) > 0) {
            $sql_ins = "INSERT INTO usuarios (" . implode(", ", $campos) . ") VALUES (" . implode(", ", $vals) . ")";
            $stmt_ins = $conexion->prepare($sql_ins);
            $stmt_ins->execute($params);
            header("Location: /proyecto-AABR/Escuela/registro/reg_usuarios.php?msg=creado");
            exit;
        }
    } else {
        $mensaje = "Ingresa usuario y contraseña para crear el registro.";
        $tipo_msg = "danger";
    }
}

// Eliminar usuario
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["eliminar_usuario"])) {
    $id_eliminar = (int)($_POST["id_usuario"] ?? 0);
    if ($id_eliminar > 0) {
        $stmt_del = $conexion->prepare("DELETE FROM usuarios WHERE `{$pk_usr}` = ?");
        $stmt_del->execute([$id_eliminar]);
    }
    header("Location: /proyecto-AABR/Escuela/registro/reg_usuarios.php?msg=eliminado");
    exit;
}

// Guardar cambios de fila
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["guardar_usuario"])) {
    $id_usuario     = (int)($_POST["id_usuario"] ?? 0);
    $puesto         = trim((string)($_POST["puesto"] ?? "Alumnos"));
    $puede_ingresar = isset($_POST["puede_ingresar"]) ? 1 : 0;
    $estatus        = ($_POST["estatus"] ?? "Activo") === "Inactivo" ? "Inactivo" : "Activo";
    $password_plain = trim((string)($_POST["password"] ?? ""));

    if ($id_usuario > 0) {
        $sets = [];
        $params = [];

        if ($col_puesto) { $sets[] = "`{$col_puesto}` = ?"; $params[] = $puesto; }
        if ($col_pass && $password_plain !== "") {
            $sets[] = "`{$col_pass}` = ?";
            $params[] = password_hash($password_plain, PASSWORD_DEFAULT);
        }
        if ($col_ing) { $sets[] = "`{$col_ing}` = ?"; $params[] = $puede_ingresar; }
        if ($col_est) { $sets[] = "`{$col_est}` = ?"; $params[] = $estatus; }

        if (count($sets) > 0) {
            $params[] = $id_usuario;
            $sql_upd = "UPDATE usuarios SET " . implode(", ", $sets) . " WHERE `{$pk_usr}` = ?";
            $stmt_upd = $conexion->prepare($sql_upd);
            $stmt_upd->execute($params);
        }
    }

    header("Location: /proyecto-AABR/Escuela/registro/reg_usuarios.php?msg=guardado");
    exit;
}

if (isset($_GET["msg"])) {
    if ($_GET["msg"] === "guardado") {
        $mensaje = "Cambios guardados correctamente.";
    } elseif ($_GET["msg"] === "eliminado") {
        $mensaje = "Usuario eliminado correctamente.";
    } elseif ($_GET["msg"] === "creado") {
        $mensaje = "Usuario creado correctamente.";
    }
}

$usuarios = $conexion->query("SELECT * FROM usuarios ORDER BY `{$pk_usr}` ASC")->fetchAll(PDO::FETCH_ASSOC);
$mostrar_nuevo = isset($_GET["nuevo"]) && $_GET["nuevo"] === "1";
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lista de Usuarios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" />
    <script defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        :root {
            --negro-fondo: #0e0b14; --negro-panel: #171221; --negro-fila-alt: #1e172c;
            --morado-oscuro: #3b1478; --morado-principal: #7c3aed; --morado-claro: #c4b5fd;
            --borde-morado: #4c2882; --blanco: #ffffff;
        }
        body { font-family: Arial, sans-serif; margin: 20px; background: var(--negro-fondo); color: var(--blanco); }
        h2, h3 { color: var(--morado-claro); }
        .nav-pills .nav-link { color: var(--blanco); background-color: var(--negro-panel); border: 1px solid var(--borde-morado); margin-right: 6px; }
        .nav-pills .nav-link:hover, .nav-pills .nav-link.active, .nav-pills .show > .nav-link { background-color: var(--morado-principal); color: var(--blanco); border-color: var(--morado-claro); }
        .dropdown-menu { background-color: var(--negro-panel); border: 1px solid var(--morado-principal); }
        .dropdown-item { color: var(--blanco); }
        .dropdown-item:hover, .dropdown-item:focus { background-color: var(--morado-oscuro); color: var(--blanco); }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin-top: 18px; margin-bottom: 16px; flex-wrap: wrap; gap: 10px; }
        .btn-morado { background: var(--morado-principal); color: var(--blanco) !important; border: 1px solid var(--blanco); padding: 7px 14px; border-radius: 6px; text-decoration: none; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
        .btn-morado:hover { background: var(--morado-oscuro); }
        .btn-accion { background: var(--morado-oscuro); color: var(--blanco) !important; border: 1px solid var(--morado-claro); padding: 6px 10px; border-radius: 4px; font-size: 13px; cursor: pointer; font-weight: bold; }
        .btn-accion:hover { background: var(--morado-principal); }
        .msg-ok { background: var(--morado-oscuro); color: var(--blanco); border: 1px solid var(--morado-claro); padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-weight: bold; }
        .panel-nuevo { background: var(--negro-panel); border: 2px solid var(--morado-principal); border-radius: 6px; padding: 16px; margin-bottom: 18px; max-width: 780px; }
        table { border-collapse: collapse; width: 100%; background: var(--negro-panel); color: var(--blanco); margin: 15px 0; }
        th { background: var(--morado-oscuro); color: var(--blanco); padding: 10px; text-align: left; border: 1px solid var(--borde-morado); }
        td { border: 1px solid var(--borde-morado); padding: 10px; vertical-align: middle; color: var(--blanco); }
        tr:nth-child(even) { background: var(--negro-fila-alt); }
        select, input[type="password"], input[type="text"] { padding: 6px 8px; background: var(--blanco); color: #111111; border: 1px solid var(--morado-principal); border-radius: 4px; }
        .pass-grupo { display: inline-flex; align-items: center; gap: 6px; }
        .acciones-grupo { display: flex; gap: 6px; flex-wrap: wrap; }
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
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_grupos.php">Grupo</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_alumno.php">Alumno</a></li>
                <li><a class="dropdown-item" href="/proyecto-AABR/Escuela/registro/reg_unidad.php">Unidad</a></li>
                <li><a class="dropdown-item active" href="/proyecto-AABR/Escuela/registro/reg_usuarios.php">Usuarios</a></li>
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

    <div class="top-bar">
        <h2>Lista de Usuarios</h2>
        <a href="/proyecto-AABR/Escuela/registro/reg_usuarios.php?nuevo=1" class="btn-morado"><i class="fa fa-user-plus"></i> Nuevo Usuario</a>
    </div>

    <?php if ($mensaje !== ""): ?>
        <div class="msg-ok"><?= htmlspecialchars($mensaje, ENT_QUOTES, "UTF-8") ?></div>
    <?php endif; ?>

    <?php if ($mostrar_nuevo): ?>
        <div class="panel-nuevo">
            <h3 class="mb-3">Registrar Nuevo Usuario</h3>
            <form method="post" action="/proyecto-AABR/Escuela/registro/reg_usuarios.php" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombre_completo" class="form-control" placeholder="Nombre completo">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Usuario</label>
                    <input type="text" name="usuario" class="form-control" required placeholder="Usuario de acceso">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Puesto</label>
                    <select name="puesto" class="form-select">
                        <option value="Alumnos">Alumnos</option>
                        <option value="Creador">Creador</option>
                        <option value="Profesores">Profesores</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Contrasena</label>
                    <input type="password" name="password" class="form-control" required placeholder="Contrasena">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Estatus</label>
                    <select name="estatus" class="form-select">
                        <option value="Activo">Activo</option>
                        <option value="Inactivo">Inactivo</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <label class="d-inline-flex align-items-center gap-2 mb-2">
                        <input type="checkbox" name="puede_ingresar" value="1" checked>
                        <span>Puede ingresar</span>
                    </label>
                </div>
                <div class="col-12 d-flex gap-2">
                    <button type="submit" name="crear_usuario" value="1" class="btn-morado">Crear Usuario</button>
                    <a href="/proyecto-AABR/Escuela/registro/reg_usuarios.php" class="btn-accion">Cancelar</a>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Nombre Completo</th>
                <th>Usuario</th>
                <th>Puesto</th>
                <th>Contrasena</th>
                <th>Estado</th>
                <th>Estatus</th>
                <th>Accion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $u): ?>
                <?php
                $id_u = (int)($u[$pk_usr] ?? 0);
                $nombre_u = (string)($col_nom && isset($u[$col_nom]) ? $u[$col_nom] : "");
                if ($nombre_u === "") {
                    $nombre_u = trim((string)(($u["apaterno"] ?? "") . " " . ($u["amaterno"] ?? "") . " " . ($u["nombre"] ?? "")));
                }
                $user_login = (string)($col_user && isset($u[$col_user]) ? $u[$col_user] : "");
                $puesto_u   = (string)($col_puesto && isset($u[$col_puesto]) ? $u[$col_puesto] : "Alumnos");
                $puede      = ((int)($col_ing && isset($u[$col_ing]) ? $u[$col_ing] : 0)) === 1;
                $est_raw    = (string)($col_est && isset($u[$col_est]) ? $u[$col_est] : "Activo");
                $estatus_u  = ($est_raw === "Inactivo" || $est_raw === "0") ? "Inactivo" : "Activo";
                ?>
                <tr>
                    <form method="post" action="/proyecto-AABR/Escuela/registro/reg_usuarios.php">
                        <td>
                            <?= htmlspecialchars((string)$id_u, ENT_QUOTES, "UTF-8") ?>
                            <input type="hidden" name="id_usuario" value="<?= $id_u ?>">
                        </td>
                        <td><?= htmlspecialchars($nombre_u, ENT_QUOTES, "UTF-8") ?></td>
                        <td><strong><?= htmlspecialchars($user_login, ENT_QUOTES, "UTF-8") ?></strong></td>
                        <td>
                            <select name="puesto">
                                <option value="Alumnos" <?= $puesto_u === "Alumnos" ? "selected" : "" ?>>Alumnos</option>
                                <option value="Creador" <?= $puesto_u === "Creador" ? "selected" : "" ?>>Creador</option>
                                <option value="Profesores" <?= $puesto_u === "Profesores" ? "selected" : "" ?>>Profesores</option>
                            </select>
                        </td>
                        <td>
                            <div class="pass-grupo">
                                <input type="password" name="password" placeholder="Nueva contrasena" autocomplete="new-password">
                                <button type="button" class="btn-accion toggle-pass" title="Mostrar/ocultar contrasena"><i class="fa fa-eye"></i></button>
                            </div>
                        </td>
                        <td>
                            <label class="d-inline-flex align-items-center gap-2 m-0">
                                <input type="checkbox" name="puede_ingresar" value="1" class="chk-ingreso" <?= $puede ? "checked" : "" ?>>
                                <span class="txt-ingreso"><?= $puede ? "Puede ingresar" : "No ingresa" ?></span>
                            </label>
                        </td>
                        <td>
                            <select name="estatus">
                                <option value="Activo" <?= $estatus_u === "Activo" ? "selected" : "" ?>>Activo</option>
                                <option value="Inactivo" <?= $estatus_u === "Inactivo" ? "selected" : "" ?>>Inactivo</option>
                            </select>
                        </td>
                        <td>
                            <div class="acciones-grupo">
                                <button type="submit" name="guardar_usuario" value="1" class="btn-accion">Guardar</button>
                                <button type="submit" name="eliminar_usuario" value="1" class="btn-accion" onclick="return confirm('Eliminar este usuario?');">Eliminar</button>
                            </div>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        document.querySelectorAll('.toggle-pass').forEach(btn => {
            btn.addEventListener('click', () => {
                const input = btn.parentElement.querySelector('input[type="password"], input[type="text"]');
                if (input) {
                    input.type = input.type === 'password' ? 'text' : 'password';
                }
            });
        });

        document.querySelectorAll('.chk-ingreso').forEach(chk => {
            chk.addEventListener('change', () => {
                const labelTxt = chk.closest('label').querySelector('.txt-ingreso');
                if (labelTxt) {
                    labelTxt.textContent = chk.checked ? 'Puede ingresar' : 'No ingresa';
                }
            });
        });
    </script>
</body>
</html>